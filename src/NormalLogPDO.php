<?php
/**
 * Normal Log Php 原生版本 (使用PDO)
 */

namespace hakumamatata\phplogger;

class NormalLogPDO
{
    /**
     * @var bool 是否紀錄LOG 開關
     */
    protected $isLog = true;
    /**
     * @var bool 是否記錄異常LOG 開關
     */
    protected $isErrorLog = true;
    /**
     * @var array 排除的actions
     */
    protected $exceptActs = [];
    /**
     * @var string 平台系統名稱
     */
    protected $applicationSystemName = '';
    /**
     * @var string 操作紀錄 分類名稱
     */
    protected $logCategoryAction = 'act_log';
    /**
     * @var string 異常紀錄 分類名稱
     */
    protected $logCategoryError = 'error_log';
    /**
     * @var string
     */
    protected $exceptionErrorMsg = '';
    /**
     * $this __construct $config
     * @var array
     */
    protected $configChecker = [
        'exceptActs' => 'array',
        'logCategoryAction' => 'string',
        'logCategoryError' => 'string',
        'isLog' => 'bool',
        'isErrorLog' => 'bool',
        'dbHost' => 'string',
        'dbUserName' => 'string',
        'dbPassword' => 'string',
        'dbType' => 'string',
        'dbName' => 'string',
        'dbTableName' => 'string',
        'dbCharset' => 'string',
    ];
    /**
     * 紀錄LOG時 參數預設值範本 (主要紀錄Yii初始配置時，無法配置以及動態的值)
     * @var array
     */
    protected $logParamsTemplate = [
        'company_id' => 0,
        'building_id' => 0,
        'user_id' => 0,
        'username' => '',
        'exceptionErrorMsg' => '',
        'controller' => '',
        'action' => '',
    ];

    /**
     * DB Server位址
     * @var string
     */
    protected $dbHost = '';
    /**
     * DB 使用者帳號名稱
     * @var string
     */
    protected $dbUserName = '';
    /**
     * DB 使用者密碼
     * @var string
     */
    protected $dbPassword = '';
    /**
     * DB 類型  預設:mysql
     * @var string
     */
    protected $dbType = 'mysql';
    /**
     * DB
     * @var string
     */
    protected $dbName = 'login';
    /**
     * 資料表名稱
     * @var string
     */
    protected $dbTableName = 'login_normal_log';
    /**
     * DB連線 charset
     * @var string
     */
    protected $dbCharset = 'utf8';
    /**
     * DB 物件 (PDO)
     * @var null|\PDO
     */
    protected $db = null;

    /**
     * 異常紀錄 LEVEL 比照YII
     */
    const LOG_LEVEL_ERROR = 1;
    /**
     * 操作紀錄 LEVEL 比照YII
     */
    const LOG_LEVEL_INFO = 4;

    /**
     * __destruct
     */
    public function __destruct()
    {
        # PDO 關閉連線
        $this->db = null;
    }

    /**
     * NormalLogPDO constructor.
     * @param $applicationSystemName
     * @param array $config
     * @throws \PDOException
     */
    public function __construct($applicationSystemName, $config = [])
    {
        $this->applicationSystemName = $applicationSystemName;

        # 處理設定值 有判別型態
        if ($config && is_array($config)) {
            foreach ($config as $key => $value) {
                if (isset($this->configChecker[$key])) {
                    switch ($this->configChecker[$key]) {
                        case 'array':
                            if (is_array($value)) {
                                $this->$key = $value;
                            }
                            break;
                        case 'string':
                            if (is_string($value)) {
                                $this->$key = $value;
                            }
                            break;
                        case 'int':
                            if (is_int($value)) {
                                $this->$key = $value;
                            }
                            break;
                        case 'bool':
                            if (is_bool($value)) {
                                $this->$key = $value;
                            }
                            break;
                    }
                }
            }
        }

        #連結資料庫
        $this->db = new \PDO(
            $this->dbType . ':host=' . $this->dbHost . ';dbname=' . $this->dbName . ';charset=' . $this->dbCharset,
            $this->dbUserName,
            $this->dbPassword);
    }

    /**
     * 紀錄 controller和actions 的相關資訊
     * @param array $logParams
     * @throws \Exception
     */
    public function recordAct($logParams = [])
    {
        if ($this->isLog) {
            $controllerId = isset($logParams['controller']) ? $logParams['controller'] : '';
            $actionId = isset($logParams['action']) ? $logParams['action'] : '';
            if (!$controllerId || !$actionId) {
                throw new \Exception('Log Require Controller Action Parameter!');
            }

            $apiRoute = $controllerId . '/' . $actionId;
            if (!in_array($apiRoute, $this->exceptActs)) {
                $logMsg = [
                    '$_GET' => (isset($_GET) && is_array($_GET)) ? $_GET : '',
                    '$_POST' => (isset($_POST) && is_array($_POST)) ? $_POST : '',
                    '$_FILES' => (isset($_FILES) && is_array($_FILES)) ? $_FILES : '',
                ];

                $this->saveLog($this->logCategoryAction, json_encode($logMsg), $logParams);
            }
        }
    }

    /**
     * 通用版本 err log
     * @param $msg
     * @param array $logParams
     * @param mixed $exception
     * @throws \Exception
     */
    public function recordErrorLog($msg, $logParams = [], $exception = null)
    {
        if ($this->isErrorLog) {
            $errMsg = [
                '$_GET' => (isset($_GET) && is_array($_GET)) ? $_GET : '',
                '$_POST' => (isset($_POST) && is_array($_POST)) ? $_POST : '',
                '$_FILES' => (isset($_FILES) && is_array($_FILES)) ? $_FILES : '',
            ];

            if ($msg && is_string($msg)) {
                $errMsg['msg'] = $msg;
            }

            if ($exception && is_object($exception)) {
                $errMsg['exception'] = [
                    'message' => method_exists($exception, 'getMessage') ? $exception->getMessage() : '',
                    'file' => method_exists($exception, 'getFile') ? $exception->getFile() : '',
                    'line' => method_exists($exception, 'getLine') ? $exception->getLine() : '',
                ];
            }

            $this->saveLog($this->logCategoryError, json_encode($errMsg), $logParams);
        }
    }

    /**
     * 儲存LOG 資料
     * @param string $category
     * @param string $msg
     * @param array $logParams
     * @throws \Exception
     */
    protected function saveLog($category, $msg, $logParams = [])
    {
        # 參數處理
        $params = $this->logParamsTemplate;
        if ($logParams && $params) {
            foreach ($params as $key => $param) {
                if (isset($logParams[$key])) {
                    $params[$key] = $logParams[$key];
                }
            }
        }

        $sql = 'INSERT INTO `' . $this->dbTableName . '`' .
            ' (`company_id`, `building_id`, `user_id`, `username`, `code`, `controller`, `action`, `category`, `level`, `prefix`, `message`, `created_at`)' .
            ' VALUES ' .
            '(:company_id, :building_id, :user_id, :username, :code, :controller, :action, :category, :level, :prefix, :message, :created_at)';
        $sth = $this->db->prepare($sql);

        $inputs = [
            ':company_id' => $params['company_id'],
            ':building_id' => $params['building_id'],
            ':user_id' => $params['user_id'],
            ':username' => $params['username'],
            ':controller' => $params['controller'],
            ':action' => $params['action'],

            ':code' => $this->applicationSystemName,
            ':category' => $category,
            ':prefix' => $this->getMessagePrefix(), # TODO
            ':message' => $msg,
            ':created_at' => (new \DateTime('now'))->format('Y-m-d H:i:s'),
        ];
        $this->exceptionErrorMsg = $params['exceptionErrorMsg'];
        switch ($category) {
            case $this->logCategoryError:
                $inputs[':level'] = self::LOG_LEVEL_ERROR;
                break;
            case $this->logCategoryAction:
            default:
                $inputs[':level'] = self::LOG_LEVEL_INFO;
                break;
        }

        if (!$sth->execute($inputs)) {
            throw new \Exception($this->exceptionErrorMsg ?:
                'Error!! NormalLogPDO->saveLog("' . $category . '", "' . $msg . '", "' . json_encode($logParams) . '") ');
        }
    }

    /**
     * 取得LOG Prefix數值
     * @return string
     */
    protected function getMessagePrefix()
    {
        # IP
        $ip = $this->getIp();

        # $sessionID
        $sessionID = $this->getSessionId();

        return '[' . $ip . '][' . $sessionID . ']';
    }

    /**
     * 取得IP (有判斷代理)
     * @return string
     */
    protected function getIp()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-';

        if (isset($_SERVER['HTTP_CLIENT_IP']) &&
            preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&
                preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
                foreach ($matches[0] AS $xip) {
                    if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                        $ip = $xip;
                        break;
                    }
                }
            }
        }

        return $ip;
    }

    /**
     * 取得 SessionId
     * @return string
     */
    protected function getSessionId()
    {
        return session_status() === PHP_SESSION_ACTIVE ? session_id() : '-';
    }
}