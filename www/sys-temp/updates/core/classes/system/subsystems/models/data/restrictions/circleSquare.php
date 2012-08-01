<?php
	class circleSquareRestriction extends baseRestriction implements iNormalizeOutRestriction {

		public function validate($value, $objectId = false) {
			return true;
		}

		public function normalizeOut($value, $objectId = false) {

			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($objectId);

			$square = null;

			if ($object instanceof umiObject && $object->getValue('diametr_sm')) {
				$square = 3.1415 * pow(($object->getValue('diametr_sm')/2), 2);
			}

			return $square;

		}
	};
?>