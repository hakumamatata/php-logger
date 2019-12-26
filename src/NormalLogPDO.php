<?php
/**
 * Normal Log Php 原生版本 (使用PDO)
 * v1.0.5.1
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
     * @var bool 是否使用session檢查 登入系統LOG
     */
    protected $isSessionCheck = true;
    /**
     * @var bool 是否會拋出異常
     */
    protected $isThrowException = false;
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
     * @var string 登入系統紀錄 分類名稱
     */
    public $logCategoryLogin = 'login_log';
    /**
     * @var string Log 本身異常捕捉分類
     */
    protected $logCategoryLogError = 'log_error_log';
    /**
     * @var string
     */
    protected $exceptionErrorMsg = '';
    /**
     * (過濾)可接收最長長度
     * @var int
     */
    protected $filterLimitLength = 1000;
    /**
     * 最高過濾階層
     * @var int
     */
    protected $filterMaxResortLevel = 3;
    /**
     * @var string 系統登入時間
     */
    protected $dateLogined = '';
    /**
     * @var string 使用者ID
     */
    protected $userId = '';
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
        'isSessionCheck' => 'bool',
        'isThrowException' => 'bool',
        'dbHost' => 'string',
        'dbUserName' => 'string',
        'dbPassword' => 'string',
        'dbType' => 'string',
        'dbName' => 'string',
        'dbTableName' => 'string',
        'dbCharset' => 'string',
        'dateLogined' => 'string',
        'userId' => 'string',
        'filterLimitLength' => 'int',
        'filterMaxResortLevel' => 'int',
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
        'created_at' => '',
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
            try {
                throw new \Exception('1234567');

                # 系統登入次數LOG
                $this->checkLoginLog($logParams);

                $controllerId = isset($logParams['controller']) ? $logParams['controller'] : '';
                $actionId = isset($logParams['action']) ? $logParams['action'] : '';
                if (!$controllerId || !$actionId) {
                    throw new \Exception('Log Require Controller Action Parameter!');
                }

                $apiRoute = $controllerId . '/' . $actionId;
                if (!in_array($apiRoute, $this->exceptActs)) {
                    $logMsg = $this->getBaseMessageData();

                    $this->saveLog($this->logCategoryAction, json_encode($logMsg), $logParams);
                }
            } catch (\Exception $e) {
                $this->selfErrorLog($e, $logParams);
            } catch (\Error $e) {
                $this->selfErrorLog($e, $logParams);
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
            try {
                $errMsg = $this->getBaseMessageData();

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
            } catch (\Exception $e) {
                $this->selfErrorLog($e, $logParams);
            } catch (\Error $e) {
                $this->selfErrorLog($e, $logParams);
            }
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
            ' (`company_id`, `building_id`, `user_id`, `username`, `code`, `method`, `controller`, `action`, `category`, `level`, `prefix`, `message`, `created_at`)' .
            ' VALUES ' .
            '(:company_id, :building_id, :user_id, :username, :code, :method, :controller, :action, :category, :level, :prefix, :message, :created_at)';
        $sth = $this->db->prepare($sql);

        $inputs = [
            ':company_id' => $params['company_id'],
            ':building_id' => $params['building_id'],
            ':user_id' => $params['user_id'],
            ':username' => $params['username'],
            ':controller' => $params['controller'],
            ':action' => $params['action'],

            ':code' => $this->applicationSystemName,
            ':method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
            ':category' => $category,
            ':prefix' => $this->getMessagePrefix(), # TODO
            ':message' => $msg,
        ];
        # 只有登入開放設定
        if ($category == $this->logCategoryLogin) {
            $inputs[':created_at'] = isset($params['created_at']) && $params['created_at'] ? $params['created_at'] :
                (new \DateTime('now'))->format('Y-m-d H:i:s');
        } else {
            $inputs[':created_at'] = (new \DateTime('now'))->format('Y-m-d H:i:s');
        }

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

    /**
     * 系統登入次數 LOG紀錄 檢查是否需要寫入 (會自動寫入)(依賴單一登入)
     * @param array $logParams
     */
    public function checkLoginLog($logParams)
    {
        # 如果已有設定好參數 即不呼叫saml取的參數
        if ($this->dateLogined && $this->userId) {
            $dateLogined = $this->dateLogined;
            $userId = $this->userId;
        } else {
            # 檢查是否存在ENV 單一登入相關參數
            if (!(getenv('SIMPLESAMLPHP_AUTOLOAD_PATH') && getenv('SIMPLESAMLPHP_AUTH_SOURCE'))) {
                # 嘗試載入ENV參數
                if (class_exists('\Dotenv\Dotenv') && getenv('ENV_CONFIG_DIR')) {
                    $dotenv = \Dotenv\Dotenv::create(getenv('ENV_CONFIG_DIR'));
                    $dotenv->overload();
                } else {
                    throw new \Exception($this->exceptionErrorMsg ?:
                        'Error!! System require simpleSAMLphp ENV parameters!');
                }
            }

            # 找出 單一登入參數
            require_once getenv('SIMPLESAMLPHP_AUTOLOAD_PATH');
            $auth = new \SimpleSAML_Auth_Simple(getenv('SIMPLESAMLPHP_AUTH_SOURCE'));
            $auth->requireAuth();

            $dateLogined = isset($auth->getAttributes()['date_logined']) ?
                $auth->getAttributes()['date_logined'][0] : null;
            $userId = isset($auth->getAttributes()['user_id']) ?
                $auth->getAttributes()['user_id'][0] : null;
        }

        if ($dateLogined && $userId) {
            if ($this->isSessionCheck && session_status() !== PHP_SESSION_DISABLED) {
                $this->loginLogSessionCheck($logParams, $userId, $dateLogined);
            } else {
                $this->recordSysLogin($dateLogined, $logParams);
            }
        }
    }

    /**
     * 系統登入次數 LOG紀錄
     * @param string $dateLogined
     * @param array $logParams
     */
    public function recordSysLogin($dateLogined = '', $logParams = [])
    {
        if ($this->isLog) {
            $sql = 'SELECT `id` FROM `' . $this->dbTableName .
                '` WHERE `code` = :code AND `category` = :category AND `created_at` = :created_at ;';
            $sth = $this->db->prepare($sql);

            $inputs = [
                ':code' => $this->applicationSystemName,
                ':category' => $this->logCategoryLogin,
                ':created_at' => $dateLogined,
            ];

            if (!$sth->execute($inputs)) {
                throw new \Exception($this->exceptionErrorMsg ?:
                    'Error!! recordSysLogin() before insert check sql error!');
            }

            $result = $sth->fetch();

            if ($result === false) {
                $logMsg = [
                    'sys_logined_time' => (new \DateTime('now'))->format('Y-m-d H:i:s'),
                ];

                # 直接記錄單一登入 登入時間
                $logParams['created_at'] = $dateLogined;

                $this->saveLog($this->logCategoryLogin, json_encode($logMsg), $logParams);
            }
        }
    }

    /**
     * session 檢查 防止重複寫入 登入系統LOG
     * @param $logParams
     * @param $sessionStatus
     * @param $userId
     * @param $dateLogined
     */
    protected function loginLogSessionCheck($logParams, $userId, $dateLogined)
    {
        $sessionStatus = session_status();

        if ($sessionStatus !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $isSave = false;
        $sysLoginTime = isset($_SESSION[$this->applicationSystemName . '.sys_login_time.' . $userId]) ?
            $_SESSION[$this->applicationSystemName . '.sys_login_time.' . $userId] : null;

        if ($sysLoginTime) {
            if ($sysLoginTime != $dateLogined) {
                $isSave = true;
            }
        } else {
            $_SESSION[$this->applicationSystemName . '.sys_login_time.' . $userId] = $dateLogined;
            $isSave = true;
        }

        if ($isSave) {
            $this->recordSysLogin($dateLogined, $logParams);
        }

        if ($sessionStatus === PHP_SESSION_NONE) {
            # 還原 session status
            @session_write_close();
        }
    }

    /**
     * 取得 欄位Message 所需的資料
     * @return array
     */
    protected function getBaseMessageData()
    {
        $gets = (isset($_GET) && is_array($_GET)) ? $_GET : [];
        $gets = $this->filterMessageData($gets);

        $posts = (isset($_POST) && is_array($_POST)) ? $_POST : [];
        $posts = $this->filterMessageData($posts);

        $messageData = [
            '$_GET' => $gets,
            '$_POST' => $posts,
            '$_FILES' => (isset($_FILES) && is_array($_FILES)) ? $_FILES : '',
        ];

        return $messageData;
    }

    /**
     * 過濾 欄位Message 所需的資料 (e.g. base64之類的資訊過長...等)
     * @param array $data
     * @param int $level
     * @return array
     */
    protected function filterMessageData($data, $level = 1)
    {
        if ($data && is_array($data)) {
            foreach ($data as $key => $get) {
                if (is_string($get) && $get) {
                    if (strlen($get) > $this->filterLimitLength) {
                        $data[$key] = substr($get, 0, $this->filterLimitLength);
                    }
                }

                if (is_array($get) && $get) {
                    if ($level <= $this->filterMaxResortLevel) {
                        # 遞迴處理 (依照參數$maxResortLevel)
                        $level++;
                        $data[$key] = $this->filterMessageData($get, $level);
                    } else {
                        # 直接將陣列當作字串處理 使用JSON
                        $tempString = json_encode($get);
                        if (strlen($tempString) > $this->filterLimitLength) {
                            $data[$key] = substr($tempString, 0, $this->filterLimitLength);
                        }
                    }
                }
            }

            return $data;
        } else {
            return [];
        }
    }

    /**
     * LOG套件異常捕捉紀錄
     * @param $e
     * @param array $logParams
     */
    protected function selfErrorLog($e, $logParams = [])
    {
        if ($this->isThrowException) {
            throw $e;
        }

        try {
            $errorMsg = [
                'status' => 'error',
                'msg' => 'LOG Error!',
                'exception' => [
                    'message' => method_exists($e, 'getMessage') ? $e->getMessage() : '',
                    'file' => method_exists($e, 'getFile') ? $e->getFile() : '',
                    'line' => method_exists($e, 'getLine') ? $e->getLine() : '',
                ],
            ];

            $this->saveLog($this->logCategoryLogError, json_encode($errorMsg), $logParams);
        } catch (\Exception $e) {
        } catch (\Error $e) {
        }
    }
}