<?php

class Sapo_Stats_Mobile_Notification
{
	private $namespace;
	private $action;
	private $notificationTargetData;
	private $notificationUserInfo;

	public function __construct($namespace, $action, Sapo_Stats_Mobile_NotificationData $data, $userData = null)
	{
		$this->setNamespace($namespace);
		$this->setAction($action);
		$this->setNotificationTargetData($data);

		if ($userData instanceOf Sapo_Stats_Mobile_NotificationUser)
		{
			$this->setNotificationUserInfo($userData);
		}
		else
		{
			$this->setNotificationUserInfo(new Sapo_Stats_Mobile_NotificationUser());
		}
	}

	public function getNotificationTargetData()
	{
		return $this->notificationTargetData;
	}

	public function setNotificationTargetData(Sapo_Stats_Mobile_NotificationData $data)
	{
		$this->notificationTargetData = $data;
	}

	public function getNotificationUserInfo()
	{
		return $this->notificationUserInfo;
	}

	public function setNotificationUserInfo(Sapo_Stats_Mobile_NotificationUser $user)
	{
		$this->notificationUserInfo = $user;
	}

	public function setNamespace($namespace)
	{
		# send app_name
		$this->namespace = str_replace('|', ' ', $namespace) . '.' . @APP_CANONICAL_HOST;
		#$this->namespace = $namespace;
	}

	public function setNamespaceWithOutAPP_NAME($namespace)
	{
		# send app_name
		$this->namespace = str_replace('|', ' ', $namespace);
		#$this->namespace = $namespace;
	}

	public function getNamespace($namespace)
	{
		return $this->namespace;
	}

	public function setAction($action)
	{
		$this->action = str_replace('|', ' ', $action);
	}

	public function getAction()
	{
		return $this->action;
	}

	public function marshall()
	{
		if (isset($_SESSION['device_spec'])) {
			$this->notificationTargetData->addExtraInfo("SPEC: " . $_SESSION['device_spec']);
		} else {
			$_SESSION['device_spec'] = false;
		}
		return '<root>'
			. '<namespace><![CDATA['.$this->namespace.']]></namespace>'
			. '<action><![CDATA['.$this->action.']]></action>'
			. '<timestamp><![CDATA['.time().']]></timestamp>'
			. $this->notificationTargetData->marshall()
			. $this->notificationUserInfo->marshall()
			. '</root>';
	}
}
