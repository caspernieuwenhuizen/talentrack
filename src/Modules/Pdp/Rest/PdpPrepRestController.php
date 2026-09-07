<?php
namespace TT\Modules\Pdp\Rest;

if ( ! defined( 'ABSPATH' ) ) exit;

use TT\Domain\Vocabularies\Enums\PdpConversationTemplate;
use TT\Infrastructure\REST\RestResponse;
use TT\Modules\Pdp\Prep\PdpPrepAccess;
use TT\Modules\Pdp\Prep\PdpPrepAnswersRepository;
use TT\Modules\Pdp\Prep\PdpPrepQuestionsRepository;
use TT\Modules\Pdp\Repositories\PdpConversationsRepository;

/**
 * PdpPrepRestController (#3305, epic #3301) — the coach preparation
 * questions and the answers given to them.
 *
 *   GET    /pdp-prep-questions[?template_key=start]  the configured sets
 *   POST   /pdp-prep-questions                       add one
 *   PATCH  /pdp-prep-questions/{id}                  change one (versions)
 *   DELETE /pdp-prep-questions/{id}                  take it out of the set
 *   PUT    /pdp-prep-questions/order                 reorder one template
 *
 *   GET    /pdp-conversations/{id}/prep              questions + answers
 *   PATCH  /pdp-conversations/{id}/prep              save answers
 *
 * The two halves have different gates. Configuring the questions is an
 * academy settings decision; answering them is per-conversation and
 * reaches only the coach who can edit that file and the head of academy —
 * never the player, never a parent, on any surface.
 *
 * **PATCH on the answers is genuinely partial.** Slice 5 autosaves against
 * it, so a request carries the one answer the coach just changed. Every
 * other answer on the conversation is left exactly as it was.
 */
class PdpPrepRestController {

    private const NS = 'talenttrack/v1';

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
    }

    public static function register(): void {
        register_rest_route( self::NS, '/pdp-prep-questions', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list_questions' ],
                'permission_callback' => [ __CLASS__, 'can_configure' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create_question' ],
                'permission_callback' => [ __CLASS__, 'can_configure' ],
            ],
        ] );

        register_rest_route( self::NS, '/pdp-prep-questions/order', [
            'methods'             => 'PUT',
            'callback'            => [ __CLASS__, 'reorder_questions' ],
            'permission_callback' => [ __CLASS__, 'can_configure' ],
        ] );

        register_rest_route( self::NS, '/pdp-prep-questions/(?P<id>\d+)', [
            [
                'methods'             => 'PUT, PATCH',
                'callback'            => [ __CLASS__, 'update_question' ],
                'permission_callback' => [ __CLASS__, 'can_configure' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'archive_question' ],
                'permission_callback' => [ __CLASS__, 'can_configure' ],
            ],
        ] );

        register_rest_route( self::NS, '/pdp-conversations/(?P<id>\d+)/prep', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_prep' ],
                'permission_callback' => [ __CLASS__, 'can_read_prep' ],
            ],
            [
                'methods'             => 'PUT, PATCH',
                'callback'            => [ __CLASS__, 'save_prep' ],
                'permission_callback' => [ __CLASS__, 'can_read_prep' ],
            ],
        ] );
    }

    public static function can_configure(): bool {
        return PdpPrepAccess::canConfigure( get_current_user_id() );
    }

    /**
     * Read and write are the same gate: there is no reader of a coach's
     * preparation who is not also entitled to change it.
     */
    public static function can_read_prep( \WP_REST_Request $r ): bool {
        return PdpPrepAccess::canAccess( get_current_user_id(), absint( $r['id'] ) );
    }

    public static function list_questions( \WP_REST_Request $r ): \WP_REST_Response {
        $repo     = new PdpPrepQuestionsRepository();
        $template = sanitize_key( (string) ( $r->get_param( 'template_key' ) ?? '' ) );

        if ( $template !== '' ) {
            if ( ! PdpConversationTemplate::isValid( $template ) ) {
                return RestResponse::error( 'bad_template',
                    __( 'Unknown conversation template.', 'talenttrack' ), 400 );
            }
            return RestResponse::success( [
                'templates' => [ $template => $repo->listForTemplate( $template ) ],
                'labels'    => PdpConversationTemplate::labelled(),
                'types'     => PdpPrepQuestionsRepository::allowedFieldTypes(),
            ] );
        }

        return RestResponse::success( [
            'templates' => $repo->listAll(),
            'labels'    => PdpConversationTemplate::labelled(),
            'types'     => PdpPrepQuestionsRepository::allowedFieldTypes(),
        ] );
    }

    public static function create_question( \WP_REST_Request $r ): \WP_REST_Response {
        $template = sanitize_key( (string) ( $r['template_key'] ?? '' ) );
        if ( ! PdpConversationTemplate::isValid( $template ) ) {
            return RestResponse::error( 'bad_template',
                __( 'Unknown conversation template.', 'talenttrack' ), 400 );
        }

        $repo = new PdpPrepQuestionsRepository();
        $id   = $repo->create( $template, self::payload( $r ) );
        if ( $id <= 0 ) {
            return RestResponse::error( 'create_failed',
                __( 'A question needs a label.', 'talenttrack' ), 400 );
        }

        return RestResponse::success( [ 'question' => $repo->find( $id ) ] );
    }

    public static function update_question( \WP_REST_Request $r ): \WP_REST_Response {
        $repo = new PdpPrepQuestionsRepository();
        $id   = absint( $r['id'] );
        if ( ! $repo->find( $id ) ) {
            return RestResponse::error( 'not_found',
                __( 'That question no longer exists.', 'talenttrack' ), 404 );
        }

        $new_id = $repo->update( $id, self::payload( $r, true ) );
        if ( $new_id <= 0 ) {
            return RestResponse::error( 'update_failed',
                __( 'A question needs a label.', 'talenttrack' ), 400 );
        }

        return RestResponse::success( [
            'question'  => $repo->find( $new_id ),
            'versioned' => $new_id !== $id,
        ] );
    }

    public static function archive_question( \WP_REST_Request $r ): \WP_REST_Response {
        $repo = new PdpPrepQuestionsRepository();
        $id   = absint( $r['id'] );
        if ( ! $repo->find( $id ) ) {
            return RestResponse::error( 'not_found',
                __( 'That question no longer exists.', 'talenttrack' ), 404 );
        }
        $repo->archive( $id );
        return RestResponse::success( [ 'archived' => $id ] );
    }

    public static function reorder_questions( \WP_REST_Request $r ): \WP_REST_Response {
        $template = sanitize_key( (string) ( $r['template_key'] ?? '' ) );
        if ( ! PdpConversationTemplate::isValid( $template ) ) {
            return RestResponse::error( 'bad_template',
                __( 'Unknown conversation template.', 'talenttrack' ), 400 );
        }

        $ids = $r['ids'] ?? [];
        if ( ! is_array( $ids ) ) {
            return RestResponse::error( 'bad_order',
                __( 'Send the question ids in the order you want them.', 'talenttrack' ), 400 );
        }

        $repo = new PdpPrepQuestionsRepository();
        $repo->reorder( $template, array_map( 'absint', $ids ) );
        return RestResponse::success( [ 'questions' => $repo->listForTemplate( $template ) ] );
    }

    public static function get_prep( \WP_REST_Request $r ): \WP_REST_Response {
        $conversation_id = absint( $r['id'] );

        $conv = ( new PdpConversationsRepository() )->find( $conversation_id );
        if ( ! $conv ) {
            return RestResponse::error( 'not_found',
                __( 'That conversation no longer exists.', 'talenttrack' ), 404 );
        }

        $template  = (string) ( $conv->template_key ?? '' );
        $questions = ( new PdpPrepQuestionsRepository() )->listForConversation( $conversation_id, $template );
        $answers   = ( new PdpPrepAnswersRepository() )->forConversation( $conversation_id );

        return RestResponse::success( [
            'conversation_id' => $conversation_id,
            'template_key'    => $template,
            'template_label'  => PdpConversationTemplate::label( $template ),
            'questions'       => $questions,
            'answers'         => array_values( $answers ),
        ] );
    }

    /**
     * Save the answers named in the request and leave the rest alone.
     *
     * Accepts `answers` as either a map of question id → text or a list of
     * `{ question_id, answer_text }` objects, because a form serialiser
     * and a fetch() send different shapes and neither should have to care.
     */
    public static function save_prep( \WP_REST_Request $r ): \WP_REST_Response {
        $conversation_id = absint( $r['id'] );

        $raw = $r['answers'] ?? null;
        if ( ! is_array( $raw ) ) {
            return RestResponse::error( 'bad_payload',
                __( 'Send the answers to save.', 'talenttrack' ), 400 );
        }

        $answers = [];
        foreach ( $raw as $key => $value ) {
            if ( is_array( $value ) && isset( $value['question_id'] ) ) {
                $answers[ (int) $value['question_id'] ] = $value['answer_text'] ?? '';
                continue;
            }
            $answers[ (int) $key ] = $value;
        }

        $written = ( new PdpPrepAnswersRepository() )->saveMany( $conversation_id, $answers );

        return RestResponse::success( [
            'conversation_id' => $conversation_id,
            'saved'           => $written,
            'answers'         => array_values(
                ( new PdpPrepAnswersRepository() )->forConversation( $conversation_id )
            ),
        ] );
    }

    /**
     * The question fields a request may set. On an update only the keys
     * actually present are returned, so a PATCH that carries a label does
     * not silently reset the field type.
     *
     * @return array<string,mixed>
     */
    private static function payload( \WP_REST_Request $r, bool $partial = false ): array {
        $out  = [];
        $keys = [ 'label', 'help_text', 'field_type', 'options', 'required' ];

        foreach ( $keys as $key ) {
            $present = $r->has_param( $key );
            if ( $partial && ! $present ) continue;

            $value = $r[ $key ] ?? null;
            switch ( $key ) {
                case 'label':
                    $out[ $key ] = sanitize_text_field( (string) $value );
                    break;
                case 'help_text':
                    $out[ $key ] = sanitize_textarea_field( (string) $value );
                    break;
                case 'field_type':
                    $out[ $key ] = sanitize_key( (string) $value );
                    break;
                case 'options':
                    $out[ $key ] = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : [];
                    break;
                case 'required':
                    $out[ $key ] = ! empty( $value ) ? 1 : 0;
                    break;
            }
        }
        return $out;
    }
}
