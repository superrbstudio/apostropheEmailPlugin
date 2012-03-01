<?php

/**
 * Makes sure every new sfGuardUser has an email_guid
 *
 * TODO: this doesn't work, I have to add this hook in each project. I'm not sure why,
 * the way I register it in the plugin configuration class seems valid
 */

class apostropheEmailGuidListener extends Doctrine_EventListener
{
	public function preInsert(Doctrine_Event $event)
	{
		// TODO: figure out why this doesn't work. For now you have to add your own preInsert hook
		// that does this simple thing, not a huge deal
		error_log("preInsert");
		$user = $event->getInvoker();
		if (is_null($user->getEmailGuid()))
		{
			$user->setEmailGuid(aGuid::generate());
		}
	}
}