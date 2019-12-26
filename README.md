hakumamatata/phplogger
=======================
LOG紀錄(Table) php 原生版本

版本更新
----
v1.0.5.1 :
更新 - 將recordAct()、recordErrorLog()兩個方法做try...catch 包裝(有開關可以設定)，增加系統穩定性，並且有錯誤紀錄。

v1.0.5 :
更新 - 抓取 message欄位的$_GET、$_POST資訊時，增加過濾長度的功能 (避免像base64...等太長的字串)。

v1.0.4.1 :
更新 - 創建式開放兩個參數(dateLogined、userId)可以設定，假使有設定時可不依賴單一登入Saml的方式就可以記錄登入系統的LOG。

安裝
----
這個套件請使用 [composer](http://getcomposer.org/download/) 安裝

執行

```
php composer.phar require hakumamatata/phplogger "*"
```

或增加

```
"hakumamatata/phplogger": "*"
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
use eztechtw\phplogger\NormalLogPDO;
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
            // 是否使用session檢查 登入系統LOG 的開關，預設值為true
            'isSessionCheck' => true,
            // DB 類型 預設值為 mysql
            'dbType' => 'mysql',
            // DB 名稱 預設值為 login
            'dbName' => 'login',
            // DB TABLE名稱 預設值為 login_normal_log
            'dbTableName' => 'login_normal_log',
            // DB 連線 charset 預設值為 utf8
            'dbCharset' => 'utf8',
            
            # 以下同樣為選填，有填寫的話，可不依賴單一登入SAML
            // 單一登入的登入時間
            'dateLogined' => '2019-09-20 13:05:40',
            // 與上一個參數共同使用 主要為記錄系統登入LOG 
            'userId' => 'string',
            
            // (過濾)可接收最長長度，預設值為1000
            'filterLimitLength' => 1000,
            // 最高過濾階層，預設值為3
            'filterMaxResortLevel' => 3,
            // 是否丟出異常或錯誤的開關，預設值為false
            'isThrowException' => false,
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


備註
----
呼叫recordAct()，組件會自動記錄 系統登入的LOG(會依賴單一登入資訊以及使用session檢查，同一登入只會記錄一次)。

增加單一登入(simpleSAMLphp)的依賴。

增加ENV套件(vlucas/phpdotenv)的依賴。
