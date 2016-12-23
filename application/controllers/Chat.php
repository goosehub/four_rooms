<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
date_default_timezone_set('America/New_York');

class Chat extends CI_Controller {

	function __construct() {
	    parent::__construct();
        $this->load->model('main_model', '', TRUE);
        $this->load->model('chat_model', '', TRUE);
	}

	// Load chats
	public function load()
	{
        // Set parameters
        $room_key = $_POST['room_key'];
        $limit = 12;

        // Get chats and reverse array
        $chats = $this->chat_model->load_chat_by_limit($room_key, $limit);
        $chats = array_reverse($chats);

        // Verify everything is okay
        echo '<div id="chat_check"></div>';

        // Echo out chats
        foreach ($chats as $chat) {
            echo '<div class="chat_message"><span class="glyphicon glyphicon-user" style="color: ' . $chat['color'] . '""></span>' 
            . $chat['username'] . ': ' . $chat['message'] . '</div>';
        }
        return true;
    }

    // For new chats
    public function new_chat()
    {                
        // Validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules('room_key', 'World Key', 'trim|required|integer|max_length[10]|callback_new_chat_validation');
        $this->form_validation->set_rules('chat_input', 'Chat Message', 'trim|required|max_length[144]');
        // $this->form_validation->set_rules('token', 'Token', 'trim|max_length[1000]');

        if ($this->form_validation->run() == FALSE) {
            return false;
        }
        // Authentication
        if (!$this->session->userdata('logged_in')) {
            return false;
        }
        $session_data = $this->session->userdata('logged_in');
        $user_id = $data['user_id'] = $session_data['id'];
        $data['user'] = $this->main_model->get_user_by_id($user_id);

        // Set variables
        $room_key = $_POST['room_key'];
        $username = $data['user']['username'];
        $color = $data['user']['color'];
        $message = htmlspecialchars($_POST['chat_input']);

        // Insert chat
        $result = $this->chat_model->new_chat($user_id, $username, $color, $message, $room_key);
        return true;
    }

    // New Chat Callback
    public function new_chat_validation()
    {
        // Authentication
        if ($this->session->userdata('logged_in')) {
            $session_data = $this->session->userdata('logged_in');
            $user_id = $data['user_id'] = $session_data['id'];
        } else {
            return false;
        }
        // Limit number of new chats in a timespan
        $chat_limit_amount = 8;
        $chat_limit_length = 60;
        $recent_chats = $this->chat_model->recent_chats($user_id, $chat_limit_length);
        if ($recent_chats > $chat_limit_amount) {
            echo 'Your talking too much';
            return false;
        }

        return true;
    }

}