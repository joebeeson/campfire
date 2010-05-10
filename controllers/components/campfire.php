<?php

	/**
	 * CampfireComponent
	 * Provides functionality related to 37signal's Campfire application.
	 * 
	 * @see http://campfirenow.com/
	 * @author Joe Beeson <jbeeson@gmail.com>
	 */
	class CampfireComponent extends Object {
		
		/**
		 * Settings
		 * Our various required and optional settings for the component
		 * @var array
		 * @access protected
		 */
		protected $settings = array(
		
			// Campfire API token
			'token' => '',
			
			// Campfire account name
			'account' => ''
		
		);
		
		/**
		 * Initialization method, executed upon startup
		 * @param Controller $controller
		 * @param array $settings
		 * @return null
		 * @access public
		 */
		public function initialize($controller, $settings = array()) {
			$this->settings = am(
				$this->settings,
				$settings
			);
		}
		
		/**
		 * Performs an API request to Basecamp
		 * @param string $url
		 * @param string $method
		 * @param array $options
		 * @return string
		 * @access protected
		 */
		protected function _request($url, $method = 'GET', $options = array()) {
			
			// Extract our settings and create our HttpSocket
			extract($this->settings);
			$socket = new HttpSocket();
			
			// Build, execute and return the response
			return $socket->request(am(array(
				'method' => $method,
				'uri'	 => array(
					'scheme' => 'http',
					'host'   => $account . '.' . 'campfirenow.com',
					'path'	 => $url,
					'user'	 => $token,
					'pass'	 => 'X'
				),
				'auth'	 => array(
					'method' => 'Basic',
					'user'   => $token,
					'pass'	 => 'X'
				),
				'header' => array(
					'User-Agent' => 'CakePHP CampfireComponent'
				)
			), $options));
		}
		
		/**
		 * Convenience method for performing a request and returning a completely
		 * setuop array representing the response
		 * @param string $url
		 * @param string $method
		 * @param array $options
		 * @return array
		 * @access protected
		 */
		protected function _fetch($url, $method = 'GET', $options = array()) {
			$response = $this->_request($url, $method, $options);
			if (substr($response, 0, 1) == '{') {
				$response = json_decode($response);
			} elseif (substr($response, 0, 1) == '<') {
				$response = $this->_xmlToArray(new SimpleXMLElement($response));
			} else {
				$response = false;
			}
			return $response;
		}
		
		/**
		 * Converts SimpleXMLElement objects into plain arrays
		 * @param mixed $payload
		 * @return array
		 * @access protected
		 */
		protected function _xmlToArray($payload = '') {
			$payload = (array) $payload;
			foreach ($payload as $key=>&$value) {
				if (is_object($value)) {
					$value = (array) $value;
				}
				if (is_array($value)) {
					$value = $this->_xmlToArray($value);
				}
			}
			return $payload;
		}

		/**
		 * Returns a list of all rooms (that we have access to) on the account
		 * @return array
		 * @access public
		 */
		public function getRooms() {
			$response = $this->_fetch('/rooms.xml');
			foreach ($response['room'] as &$room) {
				if (is_array($room['topic'])) {
					$room['topic'] = '';
				}
				$room = array_map('trim', $room);
			}
			return array('rooms' => array_values($response['room']));
		}
		
		/**
		 * Returns the ID of a room based on its name. If none is found we will
		 * return a boolean false.
		 * @return mixed
		 * @access public
		 */
		public function getRoomId($name = '') {
			$room = Set::extract(
				$this->getRooms(),
				"/rooms[name=$name]"
			);
			return (empty($room) ? false :$room[0]['rooms']['id']);
		}
		
		/**
		 * Returns details about a room. If you pass us a room name we will try
		 * to get that room's ID via getRoomId(). If we can't get any details we
		 * will return a boolean false.
		 * @return array
		 * @access public
		 */
		public function getRoomDetails($room_id) {
			if (!is_numeric($room_id)) {
				$room_id = $this->getRoomId($room_id);
			}
			if (!is_numeric($room_id)) {
				return false;
			} else {
				$response = $this->_fetch('/room/'. $room_id .'.xml');
				if (!isset($response['users'])) {
					return false;
				} else {
					$response['users'] = $response['users']['user'];
					foreach ($response['users'] as &$user) {
						unset($user['@attributes']);
					}
					return $response;
				}
			}
		}
		
		/**
		 * Sends the passed $mesasge to the $room_id. If you pass us a room name
		 * we will try to get that room's ID via getRoomId(). We return boolean
		 * to indicate success or failure.
		 * @param string $room_id
		 * @param string $message
		 * @param string $type
		 * @return boolean
		 * @access public
		 */
		public function sendMessage($room_id, $message = '', $type = 'TextMessage') {
			if (!is_numeric($room_id)) {
				$room_id = $this->getRoomId($room_id);
			}
			if (!is_numeric($room_id)) {
				return false;
			} else {
				$response = $this->_fetch(
					"/room/$room_id/speak.json",
					'POST',
					array(
						'body' => json_encode(array(
							'message' => array(
								'type' => $type,
								'body' => $message
							),
						)),
						'header' => array(
							'Content-Type' => 'application/json'
						)
					)
				);
				return ($response !== false);
			}
			return false;
		}
		
	}