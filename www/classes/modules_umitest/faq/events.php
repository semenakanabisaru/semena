<?php

// System events for faq

//$oQuestionEdeitEventListener = new umiEventListener("faq_question_edit", "faq", "onQuestionEdit");

new umiEventListener("systemSwitchElementActivity", "faq", "onChangeActivity");
new umiEventListener("systemModifyElement", "faq", "onChangeActivity");

// ---
new umiEventListener('faq_post_question', 'faq', 'onQuestionPost');

?>