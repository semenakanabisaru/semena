<?php
	abstract class __tickets_content {
		/*
			TODO:
				/content/tickets/(operation)/[id]/[?...]
		*/
		
		public function tickets () {
			$mode = getRequest('param0');
			$id = getRequest('param1');
			
			$objects = umiObjectsCollection::getInstance();
			$buffer = outputBuffer::current();
			$buffer->contentType('text/javascript');
			$buffer->option('generation-time', false);
			$buffer->clear();
			
			$json = new jsonTranslator;
			
			
			if($mode == 'create') {
				$type = selector::get('object-type')->name('content', 'ticket');
				$id = $objects->addObject(null, $type->getId());
			}
			
			if($id) {
				$ticket = selector::get('object')->id($id);
				$this->validateEntityByTypes($ticket, array('module' => 'content', 'method' => 'ticket'));
			} else {
				throw new publicException("Wrong params");
			}
			
			if($mode == 'delete') {
				$objects->delObject($id);
				$buffer->end();
			}
			
			$ticket->x = (int) getRequest('x');
			$ticket->y = (int) getRequest('y');
			$ticket->width = (int) getRequest('width');
			$ticket->height = (int) getRequest('height');
			$ticket->message = $ticket->name = getRequest('message');
			
			$url = getRequest('referer') ? getRequest('referer') : getServer('HTTP_REFERER');
			$url = str_replace("%", "&#37", $url);
			
			if($url) $ticket->url = $url;
			
			if($mode == 'create') {
				$permissions = permissionsCollection::getInstance();
				$ticket->user_id = $permissions->getUserId();
			}
			
			$ticket->commit();
			
			$data = array(
				'id' => $ticket->id
			);
			
			
			$result = $json->translateToJson($data);
			
			$buffer->push($result);
			$buffer->end();
		}
	};
?>