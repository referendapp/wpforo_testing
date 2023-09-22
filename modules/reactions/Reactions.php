<?php

namespace wpforo\modules\reactions;

use stdClass;
use wpforo\modules\reactions\classes\Template;
use wpforo\modules\reactions\classes\Actions;
use wpforo\wpforo;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Reactions {
    public $default;
    /* @var Template */ public $Template;
    /* @var Actions  */ public $Actions;

    public function __construct() {
        $this->init_defaults();
        $this->init_classes();
    }

    private function init_classes() {
        $this->Template = new Template();
        $this->Actions  = new Actions();
    }

    static public function get_types(){
        $types = (array) apply_filters( 'wpforo_reactions_set_types', [
            'up' => [
                'label'    => wpforo_phrase( 'Like', false ),
                'icon'     => sprintf( '<i class="far fa-thumbs-up" title="%1$s"></i>', wpforo_phrase( 'Like', false ) ),
                'color'    => '#3f7796',
                'reaction' => 1,
	            'order'    => 0,
            ],
            'down' => [
                'label'    => wpforo_phrase( 'Dislike', false ),
                'icon'     => sprintf( '<i class="far fa-thumbs-down" title="%1$s"></i>', wpforo_phrase( 'Dislike', false ) ),
                'color'    => '#f42d2c',
                'reaction' => - 1,
	            'order'    => 1,
            ],
        ] );

	    $order = array_column($types, 'order');
	    array_multisort($order, SORT_ASC, $types);

		return $types;
    }

    static public function get_type_list() {
        return array_keys( self::get_types() );
    }

    private function init_defaults() {
        $this->default                  = new stdClass();
        $this->default->reaction        = [
            'reactionid'  => 0,
            'userid'      => 0,
            'postid'      => 0,
            'post_userid' => 0,
            'reaction'    => 1,
            'type'        => 'up',
            'name'        => '',
            'email'       => '',
        ];
        $this->default->reaction_format = [
            'reactionid'  => '%d',
            'userid'      => '%d',
            'postid'      => '%d',
            'post_userid' => '%d',
            'reaction'    => '%d',
            'type'        => '%s',
            'name'        => '%s',
            'email'       => '%s',
        ];
        $this->default->sql_select_args = [
            'reactionid'       => null,
            'userid'           => null,
            'postid'           => null,
            'postid_include'   => [],
            'postid_exclude'   => [],
            'post_userid'      => null,
            'reaction_include' => [],
            'reaction_exclude' => [],
            'type_include'     => [],
            'type_exclude'     => [],
            'name'             => null,
            'email'            => null,
            'orderby'          => null,
            'offset'           => null,
            'row_count'        => null,
        ];
    }

    /**
     * @param $reaction
     *
     * @return array
     */
    public function decode( $reaction ) {
        $reaction                = array_merge( $this->default->reaction, (array) $reaction );
        $reaction['reactionid']  = wpforo_bigintval( $reaction['reactionid'] );
        $reaction['userid']      = wpforo_bigintval( $reaction['userid'] );
        $reaction['postid']      = wpforo_bigintval( $reaction['postid'] );
        $reaction['post_userid'] = wpforo_bigintval( $reaction['post_userid'] );
        $reaction['reaction']    = intval( $reaction['reaction'] );
        $reaction['name']        = trim( strip_tags( (string) $reaction['name'] ) );
        $reaction['email']       = sanitize_email( $reaction['email'] );
        if( ! ( $reaction['type'] = trim( strip_tags( (string) $reaction['type'] ) ) ) ) $reaction['type'] = 'up';

        return $reaction;
    }

    public function decode_item( $reaction ) {
        if( isset($reaction['reactionid']) ) $reaction['reactionid']    = wpforo_bigintval( $reaction['reactionid'] );
        if( isset($reaction['userid']) ) $reaction['userid']            = wpforo_bigintval( $reaction['userid'] );
        if( isset($reaction['postid']) ) $reaction['postid']            = wpforo_bigintval( $reaction['postid'] );
        if( isset($reaction['post_userid']) ) $reaction['post_userid']  = wpforo_bigintval( $reaction['post_userid'] );
        if( isset($reaction['reaction']) ) $reaction['reaction']        = intval( $reaction['reaction'] );
        if( isset($reaction['name']) ) $reaction['name']                = trim( strip_tags( (string) $reaction['name'] ) );
        if( isset($reaction['email']) ) $reaction['email']              = sanitize_email( $reaction['email'] );
        if( isset($reaction['type']) ) {
            if( ! ( $reaction['type'] = trim( strip_tags( (string) $reaction['type'] ) ) ) ) $reaction['type'] = 'up';
        }
        return $reaction;
    }

    /**
     * @param $reaction
     *
     * @return array
     */
    private function encode( $reaction ) {
        return $this->decode( $reaction );
    }

    /**
     * @param $reaction
     *
     * @return false|int
     */
    public function add( $reaction ) {
        $reaction = $this->encode( $reaction );
        unset( $reaction['reactionid'] );
        $reaction = wpforo_array_ordered_intersect_key( $reaction, $this->default->reaction_format );
        if( WPF()->db->insert(
            WPF()->tables->reactions,
            $reaction,
            wpforo_array_ordered_intersect_key( $this->default->reaction_format, $reaction )
        ) ) {
            $reaction['reactionid'] = WPF()->db->insert_id;
            do_action( 'wpforo_after_add_reaction', $reaction );

            return $reaction['reactionid'];
        }

        return false;
    }

    /**
     * @param array $fields
     * @param array|int $where
     * @param string $table
     *
     * @return bool
     */
    public function edit( $fields, $where, $table = '' ) {
        if( is_numeric( $where ) ) $where = [ 'reactionid' => wpforo_bigintval( $where ) ];
        $fields     = wpforo_array_ordered_intersect_key( $fields, $this->default->reaction_format );
        if( false !== WPF()->db->update(
                $table ?: WPF()->tables->reactions,
                $fields = wpforo_array_ordered_intersect_key( $this->encode( $fields ), $fields ),
                $where  = wpforo_array_ordered_intersect_key( $where, $this->default->reaction_format ),
                wpforo_array_ordered_intersect_key( $this->default->reaction_format, $fields ),
                wpforo_array_ordered_intersect_key( $this->default->reaction_format, $where )
            ) ) {
            do_action( 'wpforo_after_edit_reaction', $fields, $where );

            return true;
        }

        return false;
    }

    public function edit_for_all_active_boards( $fields, $where ) {
	    if ( $boardids = WPF()->board->get_active_boardids() ) {
		    foreach ( $boardids as $boardid ) {
			    WPF()->change_board( $boardid );
			    $this->edit( $fields, $where );
		    }
	    }
    }

    /**
     * @param array|int $args
     * @param string    $operator
     * @param string    $table
     *
     * @return bool
     */
    public function delete( $args, $operator = 'AND', $table = '' ) {
        if( is_numeric( $args ) ) $args = [ 'reactionid' => wpforo_bigintval( $args ) ];
        $operator = trim( strtoupper( (string) $operator ) );
        if( ! in_array( $operator, [ 'AND', 'OR' ], true ) ) $operator = 'AND';

        do_action( 'wpforo_before_delete_reaction', $args, $operator );

        $sql = "DELETE FROM " . ( $table ?: WPF()->tables->reactions );
        if( $wheres = $this->build_sql_wheres( $args ) ) $sql .= " WHERE " . implode( " $operator ", $wheres );

        $args = $this->parse_args( $args );
        if( $args['orderby'] ) $sql .= " ORDER BY " . $args['orderby'];
        if( $args['row_count'] ) $sql .= " LIMIT " . intval( $args['row_count'] );

        $r = WPF()->db->query( $sql );

        do_action( 'wpforo_after_delete_reaction', $args, $operator );

        return false !== $r;
    }

    public function delete_for_all_active_boards( $args, $operator = 'AND' ) {
	    if ( $boardids = WPF()->board->get_active_boardids() ) {
		    foreach ( $boardids as $boardid ) {
			    WPF()->change_board( $boardid );
			    $this->delete( $args, $operator );
		    }
	    }
    }

    private function parse_args( $args ) {
        $args                     = wpforo_parse_args( $args, $this->default->sql_select_args );
        $args                     = wpforo_array_ordered_intersect_key( $args, $this->default->sql_select_args );
        $args['postid_include']   = wpforo_parse_args( $args['postid_include'] );
        $args['postid_exclude']   = wpforo_parse_args( $args['postid_exclude'] );
        $args['reaction_include'] = wpforo_parse_args( $args['reaction_include'] );
        $args['reaction_exclude'] = wpforo_parse_args( $args['reaction_exclude'] );
        $args['type_include']     = wpforo_parse_args( $args['type_include'] );
        $args['type_exclude']     = wpforo_parse_args( $args['type_exclude'] );
        $args['orderby']          = sanitize_sql_orderby( (string) $args['orderby'] );

        return $args;
    }

    private function build_sql_wheres( $args ) {
        $args   = $this->parse_args( $args );
        $wheres = [];

        if( ! is_null( $args['reactionid'] ) ) $wheres[] = "`reactionid` = '" . wpforo_bigintval( $args['reactionid'] ) . "'";
        if( ! is_null( $args['userid'] ) ) $wheres[] = "`userid` = '" . wpforo_bigintval( $args['userid'] ) . "'";
        if( ! is_null( $args['postid'] ) ) $wheres[] = "`postid` = '" . wpforo_bigintval( $args['postid'] ) . "'";
        if( ! is_null( $args['post_userid'] ) ) $wheres[] = "`post_userid` = '" . wpforo_bigintval( $args['post_userid'] ) . "'";

        if( ! is_null( $args['name'] ) ) $wheres[] = "`name` = '" . esc_sql( $args['name'] ) . "'";
        if( ! is_null( $args['email'] ) ) $wheres[] = "`email` = '" . esc_sql( $args['email'] ) . "'";

        if( ! empty( $args['postid_include'] ) ) $wheres[] = "`postid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postid_include'] ) ) . ")";
        if( ! empty( $args['postid_exclude'] ) ) $wheres[] = "`postid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postid_exclude'] ) ) . ")";

        if( ! empty( $args['reaction_include'] ) ) $wheres[] = "`reaction` IN(" . implode( ',', array_map( 'intval', $args['reaction_include'] ) ) . ")";
        if( ! empty( $args['reaction_exclude'] ) ) $wheres[] = "`reaction` NOT IN(" . implode( ',', array_map( 'intval', $args['reaction_exclude'] ) ) . ")";

        if( ! empty( $args['type_include'] ) ) $wheres[] = "`type` IN('" . implode( "','", array_map( 'trim', $args['type_include'] ) ) . "')";
        if( ! empty( $args['type_exclude'] ) ) $wheres[] = "`type` NOT IN(" . implode( "','", array_map( 'trim', $args['type_exclude'] ) ) . "')";

        return $wheres;
    }

    /**
     * @param $args
     * @param $select
     * @param $operator
     *
     * @return string
     */
    private function build_sql_select( $args, $select = '', $operator = 'AND' ) {
        if( ! $select ) $select = '*';
        $operator = trim( strtoupper( (string) $operator ) );
        if( ! in_array( $operator, [ 'AND', 'OR' ], true ) ) $operator = 'AND';

        $sql = "SELECT $select FROM " . WPF()->tables->reactions;
        if( $wheres = $this->build_sql_wheres( $args ) ) $sql .= " WHERE " . implode( " $operator ", $wheres );

        $args = $this->parse_args( $args );
        if( $args['orderby'] ) $sql .= " ORDER BY " . $args['orderby'];
        if( $args['row_count'] ) $sql .= " LIMIT " . intval( $args['offset'] ) . "," . intval( $args['row_count'] );

        return $sql;
    }

    /**
     * @param array|numeric $args
     *
     * @return array
     */
    public function _get_reaction( $args, $operator = 'AND' ) {
        if( is_numeric( $args ) ) $args = [ 'reactionid' => wpforo_bigintval( $args ) ];

		if( WPF()->cache->on('reaction') ){
			// If there is no reaction cache it creates a new cache file
			// The cache is based on postid, each cache item contains all reactions of the postid
			$reactions = $this->get_post_reactions_and_cache( $args, $operator );
			if( is_array( $reactions ) ) return (array) array_shift( $reactions );

		}

        // In case there is no postid in the $args, the original SQL query is executed
        if( ! wpfkey( $args, 'orderby' ) ) $args['orderby'] = '`reactionid` DESC';
        $reaction = (array) WPF()->db->get_row( $this->build_sql_select( $args, '', $operator ), ARRAY_A );
        if( $reaction ) $reaction = $this->decode( $reaction );

        return $reaction;
    }

    public function get_reaction( $args, $operator = 'AND' ) {
        return wpforo_ram_get( [ $this, '_get_reaction' ], $args, $operator );
    }

    /**
     * @param array $args
     *
     * @return array
     */
    public function _get_reactions( $args = [], $operator = 'AND' ) {
        return array_map( [ $this, 'decode' ], (array) WPF()->db->get_results( $this->build_sql_select( $args, '', $operator ), ARRAY_A ) );
    }

    public function get_reactions( $args = [], $operator = 'AND' ) {
        return wpforo_ram_get( [ $this, '_get_reactions' ], $args, $operator );
    }

    public function _get_reactions_col( $col, $args = [], $operator = 'AND' ){
        $r = WPF()->db->get_col( $this->build_sql_select( $args, "`$col`", $operator ) );
        if( $this->default->reaction_format[$col] === '%d' ) $r = array_map( 'wpforo_bigintval', $r );
        return $r;
    }

    public function get_reactions_col( $col, $args = [], $operator = 'AND' ){
        return wpforo_ram_get( [ $this, '_get_reactions_col' ], $col, $args, $operator );
    }

    /**
     * @param array $args
     *
     * @return int
     */
    public function _get_count( $args = [], $operator = 'AND' ) {
        // If there is no reaction cache it creates a new cache file
        // The cache is based on postid, each cache item contains all reactions of the postid
        $reactions = $this->get_post_reactions_and_cache( $args, $operator );
        if( is_array( $reactions ) ) return count( $reactions );

        return (int) WPF()->db->get_var( $this->build_sql_select( $args, 'COUNT(*)', $operator ) );
    }

    public function get_count( $args = [], $operator = 'AND' ) {
        return wpforo_ram_get( [$this, '_get_count'], $args, $operator );
    }

    /**
     * @param array|int $args
     *
     * @return int
     */
    public function get_sum( $args = [], $operator = 'AND' ) {
        if( is_numeric( $args ) ) $args = [ 'postid' => wpforo_bigintval( $args ) ];
        return (int) WPF()->db->get_var( $this->build_sql_select( $args, 'SUM(`reaction`)', $operator ) );
    }

    public function get_reacted_count( $userid, $types = [] ){
        return $this->get_count( [ 'userid' => $userid, 'type_include' => $types ] );
    }

    public function get_received_reactions_count( $userid, $types = [] ){
        return $this->get_count( [ 'post_userid' => $userid, 'type_include' => $types ] );
    }

    public function get_post_reactions_count( $postid, $types = [] ) {
        return $this->get_count( [ 'postid' => $postid, 'type_include' => $types ] );
    }

    public function get_post_reactions_user_dnames( $postid ) {
        $rows = WPF()->db->get_results(
            WPF()->db->prepare(
                "SELECT u.`ID` as userid, u.`display_name`, u.`user_nicename` 
					FROM `" . WPF()->db->users . "` u 
					INNER JOIN `" . WPF()->tables->reactions . "` r ON r.`userid` = u.`ID`
					WHERE r.`postid` = %d 
				ORDER BY r.`userid` = %d DESC, r.`reactionid` DESC LIMIT 3",
                $postid,
                WPF()->current_userid
            ),
            ARRAY_A
        );

		return array_map(
			function( $row ){
				$row['dname'] = wpforo_user_dname( $row );
				return $row;
			}, $rows
		);
    }

    public function get_user_reaction( $postid, $userid = 0 ){
        if( ! ( $userid = wpforo_bigintval( $userid ) ) ) {
            $userid = WPF()->current_userid;
        }
        $reaction = $this->get_reaction( ['postid' => $postid, 'userid' => $userid] );
        if( $reaction ) return $reaction;
        return null;
    }

    public function get_user_reaction_reaction( $postid, $userid = 0 ){
        $reaction = $this->get_user_reaction( $postid, $userid );
        return wpfval( $reaction, 'reaction' );
    }

    public function is_reacted( $postid, $userid = 0, $type = [] ) {
        if( ! ( $userid = wpforo_bigintval( $userid ) ) ) {
            $userid = WPF()->current_userid;
        }
        return (bool) $this->get_reaction( ['postid' => $postid, 'userid' => $userid, 'type_include' => (array) $type] );
    }

    public function get_likes_for_topic( $topicid ) {
        if( $postids = WPF()->topic->get_postids( $topicid ) ){
            return $this->get_sum([
                'postid_include' => $postids,
                'type_include'   => ['up', 'down'],
            ]);
        }
        return 0;
    }

    /**
     * @param int $postid the postid in the array is required
     *
     * @return array | NULL: if the cache is not found | []: if there is no reaction | array( array(...), array(...) ): the reaction
     *
     * // $reactions can be either empty array, array of arrays or NULL
     * // 1. empty array: post has no reaction matched to the $args attributes
     * // 2. array of arrays: post has some reactions and $args matched
     * // 3. NULL: there is no reaction cache for this post or the cache is disabled
     */
    public function get_post_reactions_cache( $postid ){
        if( WPF()->cache->on('reaction') && ( $postid = wpforo_bigintval( $postid ) ) ) {
            $reactions = WPF()->cache->get_item( $postid, 'reaction');
            if( is_array( $reactions ) ) return $reactions;
        }

        return NULL;
    }

    public function filter_reactions( $args, $reactions, $operator = 'AND' ){
        $args = $this->decode_item( $args );

	    // TODO: currently the cache filter only works by $operator = 'AND' state

        foreach( $reactions as $reaction_key => $reaction ){
            $match = true;

            // Filter reactions based on requested arguments
            foreach( $args as $key => $value ){
                if( !is_array( $value ) ){
                    // If even one attribute doesn't match to the
                    // corresponding attribute of the reaction,
                    // the filter loop is stopped and $match becomes false
                    if( $value !== $reaction[$key] ) {
                        $match = false; break;
                    }
                }
                else {
                    // -----------
                    if( !empty( $value ) ){
                        if( $key === 'reaction_include' ){
                            foreach( $value as $v ){
                                if( $v !== $reaction['reactionid'] ) {
                                    $match = false; break 2;
                                }
                            }
                        }
                        elseif( $key === 'reaction_exclude' ){
                            foreach( $value as $v ){
                                if( $v === $reaction['reactionid'] ) {
                                    $match = false; break 2;
                                }
                            }
                        }
                        elseif( $key === 'type_include' ){
                            foreach( $value as $v ){
                                if( $v !== $reaction['type'] ) {
                                    $match = false; break 2;
                                }
                            }
                        }
                        elseif( $key === 'type_exclude' ){
                            foreach( $value as $v ){
                                if( $v === $reaction['type'] ) {
                                    $match = false; break 2;
                                }
                            }
                        }
                        else {
                            foreach( $value as $v ){
                                if( $v !== $reaction[$key] ) {
                                    $match = false; break 2;
                                }
                            }
                        }
                    }
                    //------------
                }
            }

            if( !$match ) {
                unset( $reactions[ $reaction_key ] );
            }
        }
        return $reactions;
    }

	public function create_reaction_cache( $postid, $reactions = [] ){
		if( WPF()->cache->on('reaction') && $postid ){
			$reactions = $reactions ?: $this->get_reactions( [ 'postid' => $postid ] );
			WPF()->cache->create( 'item', [ $postid => $reactions ], 'reaction' );
		}
	}

    public function get_post_reactions_and_cache( $args, $operator = 'AND' ){
        // If there is no reaction cache it creates a new cache file
        // The cache is based on postid, each cache item contains all reactions of the postid
        if( WPF()->cache->on('reaction') && wpfval( $args, 'postid' ) ){
	        $reactions = $this->get_post_reactions_cache( $args['postid'] );
			if( !is_array( $reactions ) ){
				//If no reaction cache found for current postid, it creates new one.
				$reactions = $this->get_reactions( [ 'postid' => $args['postid'] ] );
				$this->create_reaction_cache( $args['postid'], $reactions );
			}

            //The reactions of current postid are filtered for current $args
            return $this->filter_reactions( $args, $reactions, $operator );
        }

        return NULL;
    }
}
