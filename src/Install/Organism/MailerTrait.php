<?php
namespace App\Install\Organism;

trait MailerTrait
{
	/**
	 * @var string
	 */
	private $transport;

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var integer
	 */
	private $port;

	/**
	 * @var string
	 */
	private $encryption;

	/**
	 * @var string
	 */
	private $auth_mode;

	/**
	 * @var string
	 */
	private $sender_name;

	/**
	 * @var string
	 */
	private $sender_address;

	/**
	 * @var array
	 */
	private $swiftmailer;

	/**
	 * @var array
	 */
	private $spool;

	/**
	 * @var bool
	 */
	private $canDeliver = false;

	/**
	 * Mailer constructor.
	 *
	 * @param $params
	 */
	public function __construct($params)
	{
		if (is_array($params))
			$this->injectParameters($params);
		$this->setSpool(['type' => 'memory']);
	}

	/**
	 * @return string
	 */
	public function getTransport(): ?string
	{
		return $this->transport;
	}

	/**
	 * @param string $transport
	 *
	 * #return $this
	 */
	public function setTransport(string $transport = null)
	{
		$this->transport = $transport;

		if ($transport === 'gmail')
		{
			$this->setEncryption('ssl')->setPort(465)->setAuthMode('login');
		}
		return $this;
}

	/**
	 * @return string
	 */
	public function getHost(): ?string
	{
		return $this->host;
	}

	/**
	 * @param string $host
	 *
	 * #return $this
	 */
	public function setHost(string $host = null)
	{
		$this->host = $host;

		return $this;
}

	/**
	 * @return string
	 */
	public function getUser(): ?string
	{
		return $this->user;
	}

	/**
	 * @param string $user
	 *
	 * #return $this
	 */
	public function setUser(string $user = null)
	{
		$this->user = $user;

		return $this;
}

	/**
	 * @return string
	 */
	public function getPassword(): ?string
	{
		return $this->password;
	}

	/**
	 * @param string $password
	 *
	 * #return $this
	 */
	public function setPassword(string $password = null)
	{
		$this->password = $password;

		return $this;
}

	/**
	 * @return int
	 */
	public function getPort(): ?int
	{
		return $this->port;
	}

	/**
	 * @param int $port
	 *
	 * #return $this
	 */
	public function setPort(int $port = null)
	{
		$this->port = $port;

		return $this;
}

	/**
	 * @return string
	 */
	public function getEncryption(): ?string
	{
		return $this->encryption;
	}

	/**
	 * @param string $encryption
	 *
	 * #return $this
	 */
	public function setEncryption(string $encryption = null)
	{
		$this->encryption = $encryption;

		return $this;
}

	/**
	 * @return string
	 */
	public function getAuthMode(): ?string
	{
		return $this->auth_mode;
	}

	/**
	 * @param string $auth_mode
	 *
	 * #return $this
	 */
	public function setAuthMode(string $auth_mode = null)
	{
		$this->auth_mode = $auth_mode;

		return $this;
}

	/**
	 * @return string
	 */
	public function getSenderName(): ?string
	{
		return $this->sender_name;
	}

	/**
	 * @param string $sender_name
	 *
	 * #return $this
	 */
	public function setSenderName(string $sender_name = null)
	{
		$this->sender_name = $sender_name;

		return $this;
}

	/**
	 * @return string
	 */
	public function getSenderAddress(): ?string
	{
		return $this->sender_address;
	}

	/**
	 * @param string $sender_address
	 *
	 * #return $this
	 */
	public function setSenderAddress(string $sender_address = null)
	{
		$this->sender_address = $sender_address;

		return $this;
}

	/**
	 * @return array
	 */
	public function getSwiftmailer(): ?array
	{
		return $this->swiftmailer;
	}

	/**
	 * @param array $swiftmailer
	 *
	 * #return $this
	 */
	public function setSwiftmailer(array $swiftmailer)
	{
		$this->swiftmailer = $swiftmailer;

		return $this;
	}

	/**
	 * @param $params
	 */
	public function injectParameters($params)
	{
		$parameters = $params['parameters'];
		$this->setSwiftmailer($params['swiftmailer'])
			->setTransport($parameters['mailer_transport'])
			->setHost($parameters['mailer_host'])
			->setUser($parameters['mailer_user'])
			->setPassword($parameters['mailer_password'])
			->setPort($parameters['mailer_port'])
			->setEncryption($parameters['mailer_encryption'])
			->setAuthMode($parameters['mailer_auth_mode'])
			->setSenderName($parameters['mailer_sender_name'])
			->setSenderAddress($parameters['mailer_sender_address'])
		;

	}

	/**
	 * @return array
	 */
	public function dumpMailerSettings()
	{
		$parameters = [];
		$parameters['parameters']['mailer_transport'] = $this->getTransport();
		$parameters['parameters']['mailer_host'] = $this->getHost();
		$parameters['parameters']['mailer_user'] = $this->getUser();
		$parameters['parameters']['mailer_password'] = $this->getPassword();
		$parameters['parameters']['mailer_port'] = $this->getPort();
		$parameters['parameters']['mailer_encryption'] = $this->getEncryption();
		$parameters['parameters']['mailer_auth_mode'] = $this->getAuthMode();
		$parameters['parameters']['mailer_sender_name'] = $this->getSenderName();
		$parameters['parameters']['mailer_sender_address'] = $this->getSenderAddress();
		$parameters['parameters']['mailer_spool'] = $this->getSpool();
		$parameters['swiftmailer'] = $this->getSwiftmailer();
		return $parameters;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		$url = 'MAILER_URL=';

		switch ($this->getTransport())
		{
			case 'gmail':
				$url .= 'gmail://'.$this->getUser().':'.$this->getPassword().'@localhost';
				if (! empty($this->getEncryption()))
				{
					$url .= '?encryption=' . $this->getEncryption();
					if (! empty($this->getAuthMode()))
						$url .= '&auth_mode=' . $this->getAuthMode();
				}
				break;
			case 'smtp':
				//MAILER_URL=smtp://email-smtp.us-east-1.amazonaws.com:587?encryption=tls&username=YOUR_SES_USERNAME&password=YOUR_SES_PASSWORD
				$url .= 'smtp://' .$this->getHost();
				if (! empty($this->getPort()))
					$url .= ':' . $this->getPort();
				$fragment = [];
				if (! empty($this->getEncryption()))
					$fragment[] = 'encryption=' . $this->getEncryption();
				if (! empty($this->getUser()))
					$fragment[] = 'username=' . $this->getUser();
				if (! empty($this->getPassword()))
					$fragment[] = 'password=' . $this->getPassword();
				if (! empty($this->getAuthMode()))
					$fragment[] = 'auth_mode=' . $this->getAuthMode();
				if (!empty($fragment))
					$url .= '?' . implode('&', $fragment);
				break;
			default:
				// Disable Mailer
				$url .= 'null://localhost';
		}
		return $url;
	}

	/**
	 * @return bool
	 */
	public function isCanDeliver(): bool
	{
		return $this->canDeliver ? true : false;
	}

	/**
	 * @param bool $canDeliver
	 *
	 * #return $this
	 */
	public function setCanDeliver(bool $canDeliver)
	{
		$this->canDeliver = $canDeliver ? true : false;

		return $this;
}

	/**
	 * @return array
	 */
	public function getSpool(): array
	{
		return $this->spool;
	}

	/**
	 * @param array $spool
	 *
	 * #return $this
	 */
	public function setSpool(array $spool)
	{
		$this->spool = $spool;

		return $this;
	}
}