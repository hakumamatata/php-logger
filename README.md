hakumamatata/php-logger
=======================
LOG紀錄(Table) php 原生版本

安裝
----
這個套件請使用 [composer](http://getcomposer.org/download/) 安裝

執行

```
php composer.phar require hakumamatata/php-logger "*"
```

或增加

```
"hakumamatata/php-logger": "*"
```

到你的 `composer.json` 檔案中


環境確認
----
#### 注意運行主機是否已建置Log資料表。

DB: login

Table: login_normal_log

<br>

#### PHP擴展需開啟PDO擴展，請先檢查您的 phpinfo()，開啟操作請參照 [PHP官網](https://www.php.net/manual/en/pdo.installation.php)


使用
----
引用composer autoload:
```
require './vendor/autoload.php';
```

命名空間:
```
use hakumamatata\phplogger\NormalLogPDO;
```

創建式
 
__construct($applicationSystemName, $config = []) (參數說明如註解):
```
# PDO連線失敗時，會拋出 PDOException。
try {
    $log = new NormalLogPDO(
        // 系統名稱(必填)
        'poc', 
        [
            # 以下為必填
            // DB 位址
            'dbHost' => 'localhost',
            // DB 使用者帳號名稱
            'dbUserName' => 'user',
            // DB 使用者密碼
            'dbPassword' => 'userPassword',
        
            # 以下為選填
            // 是否有要排除紀錄的路由 controller/action 
            'exceptActs' => ['site/msg'],
            // 操作紀錄 分類名稱，預設值為'act_log'，建議各系統統一
            'logCategoryAction' => 'act_log',
            // 錯誤紀錄 分類名稱，預設值為'error_log'，建議各系統統一
            'logCategoryError' => 'error_log',
            // 是否紀錄 操作紀錄 的開關，預設值為true
            'isLog' => true,
            // 是否紀錄 操作紀錄 的開關，預設值為true
            'isErrorLog' => true,
            // DB 類型 預設值為 mysql
            'dbType' => 'mysql',
            // DB 名稱 預設值為 login
            'dbName' => 'login',
            // DB TABLE名稱 預設值為 login_normal_log
            'dbTableName' => 'login_normal_log',
            // DB 連線 charset 預設值為 utf8
            'dbCharset' => 'utf8',
        ]);
} catch (\PDOException $e) {
    // error handler
    echo 'PDO Connection failed: ' . $e->getMessage();
}
```

<br>

紀錄LOG(參數說明如以下註解):

#### 注意，recordAct()、recordErrorLog()兩個方法 資料儲存失敗時皆會拋出 \Exception!
```
# 操作紀錄: recordAct($logParams = [])
try {
    $log->recordAct([
        # 以下為必填
        // 控制器
        'controller' => 'equipment',
        // 動作
        'action' => 'View',
        
        # 以下為選填
        // 公司ID
        'company_id' => 25,
        // 建案/社區 ID
        'building_id' => 35,
        // 使用者ID
        'user_id' => 113,
        // 使用者名稱
        'username' => 'edgar',
        // 自訂拋出異常信息
        'exceptionErrorMsg' => 'LOG失敗',
    ]);
} catch (\Exception $e) {
    // error handler
    echo 'recordAct failed: ' . $e->getMessage();
}

# 異常紀錄: recordErrorLog($msg, $logParams = [], $exception = null)
# 第三個參數(非必填) $catchErrorException 為捕獲的異常對象，預設值為 null 
try {
    $log->recordErrorLog(
        '異常自訂信息', 
        [
            # 皆為選填   
            // 控制器
            'controller' => 'equipment',
            // 動作
            'action' => 'Print',
            // 公司ID
            'company_id' => 25,
            // 建案/社區 ID
            'building_id' => 35,
            // 使用者ID
            'user_id' => 113,
            // 使用者名稱
            'username' => 'edgar',
            // 自訂拋出異常信息
            'exceptionErrorMsg' => 'LOG失敗',
        ], 
        $catchErrorException);
} catch (\Exception $e) {
    // error handler
    echo 'recordErrorLog failed: ' . $e->getMessage();
}
```
