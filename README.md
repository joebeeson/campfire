# Campfire plugin for CakePHP 1.3+

Provides functionality related to 37signal's [Campfire](http://campfirenow.com/) application.

## Installation

* Download the plugin

        $ cd /path/to/your/app/plugins && git clone git://github.com/joebeeson/campfire.git

* Add the component to your `$components` and configure it there

            var $components = array(
                'Campfire' => array(
                    'auth' => 'TrLaTiehOeTiAhieP8iedoUPiaSO5SoExl9spius',
                    'account' => 'acmecorp'
                )
            );

## Methods

To retrieve a list of rooms that the authenticated user has access to, you can use the `getRooms` method.

			$rooms = $this->Campfire->getRooms();

Getting the ID for a room is also pretty simple and takes the room's name.

			$roomId = $this->Campfire->getRoomId('Room name');

To get specific details about a room use the `getRoomDetails` method which accepts either the room ID or name.

			$roomDetails = $this->Campfire->getRoomDetails('Room name');

			// Or, alternatively use the ID
			$roomDetails = $this->Campfire->getRoomDetails(28261);

Sending a message takes the room ID (or name) and the message you wish to send.

			$this->Campfire->sendMessage($roomId, 'Why hello there!');

## Example

Here's an example to send a message to all rooms that the user has access to.

			foreach ($this->Campfire->getRooms() as $room) {
				extract($room);
				$this->Campfire->sendMessage($id, 'Hello there! This is room #' . $id);
			}
