<?php
/*
* 2007-2012 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2012 PrestaShop SA
*  @version  Release: $Revision: 17004 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class MySQL extends MySQLCore
{
	/**
	 * Add SQL_NO_CACHE in SELECT queries
	 * 
	 * @var unknown_type
	 */
	public $disableCache = true;

	/**
	 * Total of queries
	 *
	 * @var int
	 */
	public $count = 0;

	/**
	 * List of queries
	 *
	 * @var array
	 */
	public $queries = array();
	
	/**
	 * List of uniq queries (replace numbers by XX)
	 * 
	 * @var array
	 */
	public $uniqQueries = array();
	
	/**
	 * List of tables
	 *
	 * @var array
	 */
	public $tables = array();

	/**
	 * Execute the query and log some informations
	 *
	 * @see DbCore::query()
	 */
	public function _mysql_query($sql, $link)
	{
		$explain = false;
		if (preg_match('/^\s*explain\s+/i', $sql))
			$explain = true;
			
		if (!$explain)
		{
			$uniqSql = preg_replace('/[0-9]+/', '<span style="color:blue">XX</span>', $sql);
			if (!isset($this->uniqQueries[$uniqSql]))
				$this->uniqQueries[$uniqSql] = 0;
			$this->uniqQueries[$uniqSql]++;

			// No cache for query
			if ($this->disableCache)
				$sql = preg_replace('/^\s*select\s+/i', 'SELECT SQL_NO_CACHE ', trim($sql));

			// Get tables in quer
			preg_match_all('/(from|join)\s+`?'._DB_PREFIX_.'([a-z0-9_-]+)/ui', $sql, $matches);
			foreach ($matches[2] as $table)
			{
				if (!isset($this->tables[$table]))
					$this->tables[$table] = 0;
				$this->tables[$table]++;
			}

			// Execute query
			$start = microtime(true);
		}
		
		$result = mysql_query($sql, $link);
		
		if (!$explain)
		{
			$end = microtime(true);
			
			// Save details
			$timeSpent = $end - $start;
			$trace = debug_backtrace(false);
			while (preg_match('@[/\\\\]classes[/\\\\]db[/\\\\]@i', $trace[0]['file']))
				array_shift($trace);
			
			$this->queries[] = array(
				'query' => $sql,
				'time' => $timeSpent,
				'file' => isset($trace[1])?$trace[1]['file']:'',
				'line' => isset($trace[1])?$trace[1]['line']:'',
			);
		}
		
		return $result;
	}


	public function getRow($query, $use_cache = 1)
	{
		$query .= ' LIMIT 1';
		$this->_result = false;
		$this->_lastQuery = $query;
		if ($use_cache && _PS_CACHE_ENABLED_)
			if ($result = Cache::getInstance()->get(md5($query)))
			{
				$this->_lastCached = true;
				return $result;
			}
		if ($this->_link)
			if ($this->_result = self::_mysql_query($query, $this->_link))
			{
				$this->_lastCached = false;
				if (_PS_DEBUG_SQL_)
					$this->displayMySQLError($query);
				$result = mysql_fetch_assoc($this->_result);
				if ($use_cache = 1 && _PS_CACHE_ENABLED_)
					Cache::getInstance()->setQuery($query, $result);
				return $result;
			}
		if (_PS_DEBUG_SQL_)
			$this->displayMySQLError($query);
		return false;
	}

	public function getValue($query, $use_cache = 1)
	{
		$query .= ' LIMIT 1';
		$this->_result = false;
		$this->_lastQuery = $query;
		if ($use_cache && _PS_CACHE_ENABLED_)
			if ($result = Cache::getInstance()->get(md5($query)))
			{
				$this->_lastCached = true;
				return $result;
			}
		if ($this->_link && $this->_result = self::_mysql_query($query, $this->_link))
		{
			if ($tmpArray = mysql_fetch_row($this->_result))
			{
				$this->_lastCached = false;
				if ($use_cache && _PS_CACHE_ENABLED_)
					Cache::getInstance()->setQuery($query, $tmpArray[0]);
				return $tmpArray[0];
			}
		}
		return false;
	}

	public function Execute($query, $use_cache = 1)
	{
		$this->_result = false;
		if ($this->_link)
		{
			$this->_result = self::_mysql_query($query, $this->_link);
			if (_PS_DEBUG_SQL_)
				$this->displayMySQLError($query);
			if ($use_cache AND _PS_CACHE_ENABLED_)
				Cache::getInstance()->deleteQuery($query);
			return $this->_result;
		}
		if (_PS_DEBUG_SQL_)
			$this->displayMySQLError($query);
		return false;
	}

	/**
	 * ExecuteS return the result of $query as array,
	 * or as mysqli_result if $array set to false
	 *
	 * @param string $query query to execute
	 * @param boolean $array return an array instead of a mysql_result object
	 * @param int $use_cache if query has been already executed, use its result
	 * @return array or result object
	 */
	public function ExecuteS($query, $array = true, $use_cache = 1)
	{
		$this->_result = false;
		$this->_lastQuery = $query;
		if ($use_cache && _PS_CACHE_ENABLED_ && $array && ($result = Cache::getInstance()->get(md5($query))))
		{
			$this->_lastCached = true;
			return $result;
		}
		if ($this->_link && $this->_result = self::_mysql_query($query, $this->_link))
		{
			$this->_lastCached = false;
			if (_PS_DEBUG_SQL_)
				$this->displayMySQLError($query);
			if (!$array)
				return $this->_result;
			$resultArray = array();
			// Only SELECT queries and a few others return a valid resource usable with mysql_fetch_assoc
			if ($this->_result !== true)
				while ($row = mysql_fetch_assoc($this->_result))
					$resultArray[] = $row;
			if ($use_cache && _PS_CACHE_ENABLED_)
				Cache::getInstance()->setQuery($query, $resultArray);
			return $resultArray;
		}
		if (_PS_DEBUG_SQL_)
			$this->displayMySQLError($query);
		return false;
	}

	public function delete($table, $where = false, $limit = false, $use_cache = 1)
	{
		$this->_result = false;
		if ($this->_link)
		{
			$query  = 'DELETE FROM `'.bqSQL($table).'`'.($where ? ' WHERE '.$where : '').($limit ? ' LIMIT '.(int)$limit : '');
			$res =  self::_mysql_query($query, $this->_link);
			if ($use_cache && _PS_CACHE_ENABLED_)
				Cache::getInstance()->deleteQuery($query);
			return $res;
		}

		return false;
	}

	protected function q($query, $use_cache = 1)
	{
		global $webservice_call;
		$this->_result = false;
		if ($this->_link)
		{
			$result =  self::_mysql_query($query, $this->_link);
			if ($webservice_call)
				$this->displayMySQLError($query);
			if ($use_cache && _PS_CACHE_ENABLED_)
				Cache::getInstance()->deleteQuery($query);
			return $result;
		}
		return false;
	}
}