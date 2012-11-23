<?php
/*
*  @author ORS <admin@prestalab.ru>
*  @copyright  2011 PrestaLab.RU
*  @version  Release: $Revision$
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

class CacheAPCCore extends Cache
{

	protected function __construct()
	{
		parent::__construct();
		return $this->_setKeys();
	}

	public function set($key, $value, $expire = 0)
	{
		if (apc_store($key, $value, $expire))
		{
			$this->_keysCached[$key] = true;
			$this->_writeKeys();
			return $key;
		}
	}

	public function get($key)
	{
		if (!isset($this->_keysCached[$key]))
			return false;
		return apc_fetch($key);
	}

	protected function _setKeys()
	{
		$this->_keysCached = apc_fetch('keysCached');
		$this->_tablesCached = apc_fetch('tablesCached');
		
		return true;
	}
	
	public function setNumRows($key, $value, $expire = 0)
	{
		return $this->set($key.'_nrows', $value, $expire);
	}
	
	public function getNumRows($key)
	{
		return $this->get($key.'_nrows');
	}


	public function setQuery($query, $result)
	{
		if ($this->isBlacklist($query))
			return true;
		$md5_query = md5($query);
		if (isset($this->_keysCached[$md5_query]))
			return true;
		$key = $this->set($md5_query, $result);
		if(preg_match_all('/('._DB_PREFIX_.'[a-z_-]*)`?.*/i', $query, $res))
			foreach($res[1] AS $table)
				if(!isset($this->_tablesCached[$table][$key]))
					$this->_tablesCached[$table][$key] = true;
		$this->_writeTables();
	}
	
	public function delete($key, $timeout = 0)
	{
		if (!empty($key) AND apc_delete($key))
			unset($this->_keysCached[$key]);
		$this->_writeKeys();
	}

	public function deleteQuery($query)
	{
		if (preg_match_all('/('._DB_PREFIX_.'[a-z_-]*)`?.*/i', $query, $res))
			foreach ($res[1] AS $table)
				if (isset($this->_tablesCached[$table]))
				{
					foreach ($this->_tablesCached[$table] AS $apcKey => $foo)
					{
						$this->delete($apcKey);
						$this->delete($apcKey.'_nrows');
					}
					unset($this->_tablesCached[$table]);
				}
		$this->_writeTables();
	}

	private function _writeKeys()
	{
		$this->set('keysCached', $this->_keysCached, 0, 0);
	}

	private function _writeTables()
	{
		$this->set('tablesCached', $this->_tablesCached, 0, 0);
	}

	public function flush()
	{
		if (apc_clear_cache('user'))
			return $this->_setKeys();
		return false;
	}

	public function __destruct()
	{
		parent::__destruct();
		apc_store('keysCached', $this->_keysCached);
		apc_store('tablesCached', $this->_tablesCached);
	}
}