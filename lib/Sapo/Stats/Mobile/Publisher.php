<?php

class Sapo_Stats_Mobile_Publisher
{
	const MSG_SYNC_HOST = "services02.mobile.bk.sapo.pt";
	const MSG_SYNC_PORT = 8080;

	public static function send($event)
	{
		if (($socket=fsockopen(self::MSG_SYNC_HOST, self::MSG_SYNC_PORT, $errno, $errstr, 5)))
		{
			$server_name = Sapo_HTTP_Server::getCanonicalServerName();
			$message = $event->marshall();
			$out = "POST /tracker/Rest?server=".$server_name." HTTP/1.1\r\n"
				."Host: services02.mobile.bk.sapo.pt\r\n"
				."Content-Length: ".strlen($message)."\r\n"
				."Content-Type: text/xml; charset=utf-8"
				."Connection: close\r\n"
				."\r\n";

			$out .= $message;
			fputs($socket, $out);
			fclose($socket);
		}
		return true;
	}

	public static function publish($event)
	{
		$notificationUser = new Sapo_Stats_Mobile_NotificationUser();
		$notificationUser->setIdentifier(Sapo_Helpers_Cookie::getCookie(Sapo_Context::USER_TRACKING_COOKIE));

		$objectId = $objectDesc = null;

		switch(get_class($event))
		{
			case 'Sapo_Stats_Event_PageView':
				$objectId = $event->getObjectId();
				$objectDesc = $event->getObjectDescription();
				break;

			case 'Sapo_Stats_Event_Action':
				$objectId = $event->getActionOrigin() . '_' . $event->getActionName();
				$objectDesc = $event->getActionName() . ' em ' . $event->getActionOrigin();
				break;

			case 'Sapo_Stats_Event_Error':
				$objectId = $event->getService() . '_' . $event->getOperation();
				$objectDesc = 'erro em ' . $event->getService() . '::' . $event->getOperation();
				break;
		}

		$noticationData = new Sapo_Stats_Mobile_NotificationData($objectId, $objectDesc);

if (defined('DEBUG_STATS') && DEBUG_STATS) Sapo_Log::err(sprintf('STAT LOG (%s, %s,   %s, %s)', $event->getSection(), $event->getPage(), $objectId, $objectDesc));

		$metric = $event->metrics[0];
//		foreach ($event->metrics as $metric)
//		{
			foreach($event->string_attributes as $extra)
				$noticationData->addExtraInfo($extra);

			if (get_class($metric) != 'Sapo_Stats_Metric_PageView')
				$noticationData->addExtraInfo(Sapo_Stats_Mobile_NotificationData::DISCARD_PAGEVIEW);
			else
			{
				$error = $event->getPageErrorType();
				if ($error) $noticationData->addExtraInfo('ERROR: ' . $error);
			}

			if (get_class($metric) == 'Sapo_Stats_Metric_Error')
				$noticationData->addExtraInfo('params=' . $event->getParams());

			$brokerNotification = new Sapo_Stats_Mobile_Notification($event->getSection(), $event->getPage(), $noticationData, $notificationUser);
			self::send($brokerNotification);
//		}
	}
}
