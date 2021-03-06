<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// echo '<br>' . $this->db->last_query() . '<br>';

Class main_model extends CI_Model
{
    function select_row($table, $id)
    {
        $this->db->select('*');
        $this->db->from($table);
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->result_array();
    }

    function insert_row($table, $column)
    {
        $data = array(
            'column' => $column,
        );
        $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    function update_row($table, $id, $column)
    {
        $data = array(
            'column' => $column,
        );
        $this->db->where('id', $id);
        $this->db->update($table, $data);
    }

    function delete_row($table, $id)
    {
        $this->db->where('id', $id);
        $this->db->delete($table);
    }

    function load_message_by_limit($room_key, $limit)
    {
        $this->db->select('*');
        $this->db->from('message');
        $this->db->where('room_key', $room_key);
        $this->db->limit($limit);
        $this->db->order_by('id', 'desc');
        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    function load_message_by_last_message_id($room_key, $last_message_id)
    {
        $this->db->select('*');
        $this->db->from('message');
        $this->db->where('room_key', $room_key);
        $this->db->where('id >', $last_message_id);
        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    function new_message($user_key, $username, $color, $message, $room_key)
    {
        $data = array(
            'user_key' => $user_key,
            'username' => $username,
            'color' => $color,
            'message' => $message,
            'room_key' => $room_key,
        );
        $this->db->insert('message', $data);
    }

    function recent_messages($user_key, $message_limit_length)
    {
        $query = $this->db->query("
            SELECT COUNT(id) as recent_messages
            FROM `message`
            WHERE `user_key` = '" . $user_key . "'
            AND `timestamp` > (now() - INTERVAL " . $message_limit_length . " SECOND);
        ");
        $result = $query->result_array();
        return isset($result[0]['recent_messages']) ? $result[0]['recent_messages'] : false;
    }

    function get_user_by_id($id)
    {
        $this->db->select('*');
        $this->db->from('user');
        $this->db->where('id', $id);
        $query = $this->db->get();
        $result = $query->result_array();
        return isset($result[0]) ? $result[0] : false;
    }

    function get_room_by_id($id)
    {
        $this->db->select('*');
        $this->db->from('room');
        $this->db->where('id', $id);
        $query = $this->db->get();
        $result = $query->result_array();
        return isset($result[0]) ? $result[0] : false;
    }

    function get_room_by_slug($slug)
    {
        $this->db->select('*');
        $this->db->from('room');
        $this->db->where('slug', $slug);
        $query = $this->db->get();
        $result = $query->result_array();
        return isset($result[0]) ? $result[0] : false;
    }

    function get_available_room($room_capacity)
    {
        $room_capacity = (int) $room_capacity;
        $query = $this->db->query("
            SELECT room.* 
            FROM room
            LEFT JOIN user
                ON room.id = user.room_key
            WHERE room.archived = 0
            AND user.archived = 0
            GROUP BY room.id
            HAVING COUNT(user.id) < " . $room_capacity . "
            ORDER BY RAND();"
            );
        $result = $query->result_array();
        return isset($result[0]) ? $result[0] : false;
    }

    function get_users_in_room($room_id)
    {
        $room_id = (int) $room_id;
        $query = $this->db->query("
            SELECT room.*, user.* 
            FROM room
            LEFT JOIN user
                ON room.id = user.room_key
            WHERE user.archived = 0
            AND room.id = " . $room_id . ";"
            );
        $result = $query->result_array();
        return $result;
    }

    function create_room($slug)
    {
        $data = array(
            'slug' => $slug,
            'last_load' => date('Y-m-d H:i:s', time()),
            'created' => date('Y-m-d H:i:s', time()),
        );
        $this->db->insert('room', $data);
        $room_id = $this->db->insert_id();

        // System welcome message
        $message = $this->system_start_room_message();
        $result = $this->main_model->new_message($this->system_user_id, $this->system_start_room_slug, '#000000', $message, $room_id);

        return $room_id;
    }

    function system_start_room_message()
    {
        $message = "";
        $message .= "Welcome! You&#39;re the first! Others will join soon. Some tips: Embed Youtube, Vimeo, Twitch, SoundCloud, Vocaroo, and Images by posting the URL. Pin posts to keep in view as you chat. Share this url to invite others to join directly.";
        return $message;
    }

    function create_user($room_key, $username, $location, $color, $ip)
    {
        $data = array(
            'room_key' => $room_key,
            'username' => $username,
            'location' => $location,
            'color' => $color,
            'ip' => $ip,
            'last_load' => date('Y-m-d H:i:s', time()),
            'created' => date('Y-m-d H:i:s', time()),
        );
        $this->db->insert('user', $data);
        return $this->db->insert_id();
    }

    function update_user_last_load($user_id)
    {
        $data = array(
            'last_load' => date('Y-m-d H:i:s', time()),
        );
        $this->db->where('id', $user_id);
        $this->db->update('user', $data);
    }

    function update_room_last_load($room_key)
    {
        $data = array(
            'last_load' => date('Y-m-d H:i:s', time()),
        );
        $this->db->where('id', $room_key);
        $this->db->update('room', $data);
    }

    function inactive_users($inactive_wait_time)
    {
        $query = $this->db->query("
            SELECT *
            FROM `user`
            WHERE `archived` = 0
            AND `last_load` < (now() - INTERVAL " . $inactive_wait_time . " SECOND);
        ");
        return $query->result_array();
    }

    function archive_user_by_id($user_id)
    {
        $data = array(
            'archived' => 1,
        );
        $this->db->where('id', $user_id);
        $this->db->update('user', $data);
    }

    function inactive_rooms($inactive_wait_time)
    {
        $query = $this->db->query("
            SELECT *
            FROM `room`
            WHERE `archived` = 0
            AND `last_load` < (now() - INTERVAL " . $inactive_wait_time . " SECOND);
        ");
        return $query->result_array();
    }

    function archive_room_by_id($room_id)
    {
        $data = array(
            'archived' => 1,
        );
        $this->db->where('id', $room_id);
        $this->db->update('room', $data);
    }
}
?>