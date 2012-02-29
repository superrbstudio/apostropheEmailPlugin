<?php

/**
 * Makes sure every new sfGuardUser has an email_guid
 */

class apostropheEmailGuidListener extends Doctrine_Record_Listener
{
	public function preInsert(Doctrine_Event $event)
	{
		$user = $event->getInvokder();
		if (is_null($user->getEmailGuid()))
		{
			$user->setEmailGuid(aGuid::generate());
		}
	}
}