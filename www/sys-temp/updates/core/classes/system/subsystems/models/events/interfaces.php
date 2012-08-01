<?php
	interface iUmiEventListener {
		public function __construct($eventId, $callbakModule, $callbackMethod);

		public function setPriority($priority = 5);
		public function getPriority();

		public function setIsCritical($isCritical = false);
		public function getIsCritical();


		public function getEventId();
		public function getCallbackModule();
		public function getCallbackMethod();
	};

	interface iUmiEventPoint {
		public function __construct($eventId);
		public function getEventId();

		public function setMode($eventPointMode = "process");
		public function getMode();

		public function setParam($paramName, $paramValue = NULL);
		public function addRef($paramName, &$paramValue);

		public function getParam($paramName);
		public function &getRef($refName);
		public function call();

	};

	interface iUmiEventsController {
		public function callEvent(iUmiEventPoint $eventPoint);
		static public function registerEventListener(iUmiEventListener $eventListener);
	};
?>