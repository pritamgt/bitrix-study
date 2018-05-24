<?php
namespace Bitrix\Replica\Log;

class Message
{
	protected $from;
	protected $to;
	protected $event;

	/**
	 * Message constructor.
	 *
	 * @param string $from Source database.
	 * @param array $to Target list.
	 * @param string $event Message.
	 */
	public function __construct($from = '', $to = array(), $event = '')
	{
		$this->from = (string)$from;

		if (is_array($to))
		{
			$this->to = array_values($to);
		}
		else
		{
			$this->to = array();
		}

		$this->event = $event;
	}

	/**
	 * Creates new instance from event message.
	 *
	 * @param string $event Encoded message.
	 *
	 * @return Message|null
	 */
	public static function createFromEvent($event)
	{
		$unpacked = unserialize($event);
		if (
				is_array($unpacked)
				&& isset($unpacked["from"])
				&& isset($unpacked["to"])
				&& isset($unpacked["event"])
		)
		{
			$result = new self($unpacked["from"], $unpacked["to"], $event);
		}
		else
		{
			$result = null;
		}
		return $result;
	}

	/**
	 * Creates new instance from $_POST.
	 *
	 * @param array $post POST variable.
	 *
	 * @return Message|null
	 */
	public static function createFromPost($post)
	{
		if (
				is_array($post)
				&& isset($post["NODE_FROM"])
				&& isset($post["NODE_TO"])
				&& isset($post["EVENT"])
		)
		{
			$result = new self($post["NODE_FROM"], array($post["NODE_TO"]), $post["EVENT"]);
		}
		else
		{
			$result = null;
		}
		return $result;
	}

	/**
	 * Returns source database.
	 *
	 * @return string
	 */
	public function getFrom()
	{
		return $this->from;
	}

	/**
	 * Sets source database.
	 *
	 * @param string $from Source database.
	 *
	 * @return void
	 */
	public function setFrom($from)
	{
		$this->from = (string)$from;
	}

	/**
	 * Returns array of target databases.
	 *
	 * @return array
	 */
	public function getTo()
	{
		return $this->to;
	}

	/**
	 * Sets target databases list.
	 *
	 * @param array $to Target list.
	 *
	 * @return void
	 */
	public function setTo($to)
	{
		if (is_array($to))
		{
			$this->to = array_values($to);
		}
		else
		{
			$this->to = array();
		}
	}

	/**
	 * Returns unpacked message.
	 *
	 * @return string
	 */
	public function getEvent()
	{
		return unserialize($this->event);
	}

	/**
	 * Packs and replaces message.
	 *
	 * @param array $event The message.
	 *
	 * @return void
	 */
	public function setEvent($event)
	{
		$this->event = serialize($event);
	}

	/**
	 * Returns packed message.
	 *
	 * @return string
	 */
	public function getRawEvent()
	{
		return $this->event;
	}

	/**
	 * Returns identifier of the command.
	 * This identifier will allow us to skip "duplicates".
	 *
	 * @return string
	 */
	public function getCommandId()
	{
		$event = unserialize($this->event);
		if (is_array($event))
		{
			unset($event["event"]["ts"]);
			unset($event["event"]["ip"]);
		}
		return md5(serialize($event));
	}

	/**
	 * Packs the message.
	 *
	 * @return string
	 */
	public function pack()
	{
		return serialize(array(
			"from" => $this->from,
			"to" => $this->to,
			"event" => $this->event,
		));
	}

	/**
	 * Returns data for POST to target database.
	 *
	 * @param string $nodeTo Target database.
	 * @param string $signature Event signature.
	 *
	 * @return array
	 */
	public function getPost($nodeTo, $signature)
	{
		return array(
			"SIGNATURE" => $signature,
			"NODE_FROM" => $this->from,
			"NODE_TO" => $nodeTo,
			"EVENT" => $this->event,
		);
	}
}
