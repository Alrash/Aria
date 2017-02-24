<?php
/**
 * Author: Alrash
 * Date: 2017/01/23 12:15
 * Description: 数据库类
 *
 * -----------------------------------------------------------------------------------------
 * (1) 支持直接使用sql语句执行（query($sql)）
 * (2) 支持直接使用占位符的sql语言执行(queryPlaceHolder($sql)->bind([]))
 * (3) 支持不使用占位符和使用占位符的拼接sql语句
 *      select insert update delete where and or limit (order by) having (group by)
 *      其中where 只支持 in/not in/like/between(只支持占位符的形式)
 * -----------------------------------------------------------------------------------------
 * 部分方法说明：
 * (1) exec()           执行sql语句
 * (2) bind([])         先绑定数据（设置的一条sql语句所有的），再提交（事务，失败会回滚）
 * (3) bindRows([[]])   与上一条差不多，不过是执行多次（批量）
 * (4) fetch()          返回结果集下一行
 * (5) fetchAll()       返回所有结果集
 * (6) fetchOne(int)    返回某一行结果
 * (7) fetchColumn(int) 返回结果集下一行某一列
 * (8) lastInsertId()   返回插入时自动增长键最后一个值
 * (9) rowCount()       返回影响行数
 * (10) other($sql)     追加一部分sql
 * -----------------------------------------------------------------------------------------
 */

namespace Aria\db;

use Aria\base\Component;
use Aria\base\SetMethodLimitTrait;

class DataBase extends Component {
    use SetMethodLimitTrait;

    //柄句
    private $handle;
    private $stmt;

    private $sql = '';
    private $sql_copy = '';
    private $flag = false;

    //配置信息
    private $dbName;
    private $user;
    private $password;
    private $host = '127.0.0.1';
    private $port = '3306';

    /**
     * 重载初始化
     * 为了链接数据库
     * @param array $config
     * @param array $params
     */
    public function init(array $config = [], array $params = []){
        parent::init($config, $params);

        try {
            $dsn = sprintf("mysql:host=%s:%s;dbname=%s;charset=utf8", $this->host, $this->port, $this->dbName);
            $option = [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC];
            $this->handle = new \PDO($dsn, $this->user, $this->password, $option);
            $this->handle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * 直接执行完整的sql语句
     * @param $sql      完整的sql语句
     * @return $this
     */
    public function query($sql) {

        try{
            $this->sql = $sql;
            $this->stmt = $this->handle->prepare($this->sql);
            $this->handle->beginTransaction();
            $this->stmt->execute();
            $this->handle->commit();
            $this->reset();
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
            $this->handle->rollBack();
        }
        return $this;
    }

    /**
     * 执行使用占位符语句用
     * @param $sql
     * @return $this
     */
    public function queryPlaceHolder($sql) {
        $this->sql = $sql;
        return $this;
    }

    /**
     * select *** from ***
     * @param $column_name  列名，可以是数组表示，若是['password' => 'pwd']，则表示select `password` as `pwd` from ***
     * @param $tables       表名，基本同上
     * @return $this
     */
    public function select($column_name, $tables) {

        $this->sql .= 'SELECT ';
        //填充成数组
        if (!is_array($column_name)) {
            $column_name = [$column_name];
        }
        $fillingArray = [];
        foreach ($column_name as $origin_name => $as_name) {
            //拆分再整合，是为了多表查询用的情况
            $as_name = implode(explode('.', $as_name), '`.`');
            if (is_int($origin_name)) {
                array_push($fillingArray, '`' . $as_name . '`');
            }else {
                $origin_name = implode(explode('.', $origin_name), '`.`');
                array_push($fillingArray, '`' . $origin_name . '` AS `' . $as_name . '`');
            }
        }
        //select [a, b, c] from ***
        $this->sql .= implode(', ', $fillingArray);
        //防止select `*` from xxx 这样的语句（不被允许）
        $this->sql = preg_replace('/`\*`/', '*', $this->sql, 1);

        //以下基本同上
        $this->sql .= ' FROM ';
        if (!is_array($tables)) {
            $tables = [$tables];
        }
        $fillingArray = [];
        foreach ($tables as $origin_name => $table_name) {
            if (is_int($origin_name)) {
                array_push($fillingArray, '`' . $table_name . '`');
            }else {
                array_push($fillingArray, '`' . ltrim($origin_name, '#') . '` AS `' . $table_name . '`');
            }
        }
        $this->sql .= implode(', ', $fillingArray);

        return $this;
    }

    /**
     * insert into table_name(column_list) values(value_list)
     *
     * 当参数三为false时，参数二的用法：
     * 列名 => 值 / 列名（使用占位符）
     * 如：
     * ['note' => 'test', 'password', 'email' => 'kasukuikawai@gmail.com']
     * 解释成：
     * insert into table(`note`, `password`, `email`) values('test', ?, 'kasukuikawai@gmail.com')
     *
     * @param string $table     表名
     * @param array $map        列名与键值对 | 但是需要插入的值（需全部列），参数三决定
     * @param bool $is_value    判别是否为全值，默认不是(false)
     * @return $this
     */
    public function insert(string $table, array $map, bool $is_value = false) {
        $this->sql .= 'INSERT INTO `' . $table . '`';

        $tmpMap = $map;
        if ($is_value) {
            $map = [];
            foreach ($tmpMap as $key => $value) {
                $map[$key] = '\'' . $value . '\'';
            }
        }else {
            foreach ($tmpMap as $column => $value) {
                if (is_int($column)) {
                    //处理没有值的项
                    $map[$value] = '?';
                    unset($map[$column]);
                }else {
                    //=> values('1', '2' ...)
                    $map[$column] = '\'' . $value . '\'';
                }
            }
            //填充需要列
            $this->sql .= '(`' . implode('`, `', array_keys($map)) . '`)';
        }

        $this->sql .= ' VALUES(' . implode(', ', $map) . ')';

        return $this;
    }

    /**
     * update *** set column = value
     * @param string $table     表名
     * @param array $map        可单为字符串
     * @return $this
     */
    public function update(string $table, $map = []) {
        $this->sql .= 'UPDATE `' . $table . '` SET ';

        if (!is_array($map)) {
            $map = [$map];
        }

        /*
         * $map = ['note' => 'normal user', 'password']
         * =======>
         * `note` = 'normal user', `password` = ?
         * */
        $params = [];
        foreach ($map as $column => $value) {
            if (is_int($column)) {
                array_push($params, '`' . $value . '` = ?');
            }else {
                array_push($params, '`' . $column . '` = \'' . $value . '\'');
            }
        }
        $this->sql .= implode(', ', $params);

        return $this;
    }

    /**
     * delete from table
     * @param string $table
     * @return $this
     */
    public function delete(string $table) {
        $this->sql .= 'DELETE from `' . $table . '`';
        return $this;
    }

    /**
     * where 条件
     * @param string $column_name   列名
     * @param string $compare       待执行操作，支持in like not in，between只支持占位符（参数三无用）
     * @param string $value         值，默认占位符?
     * @return $this
     */
    public function where(string $column_name, string $compare, string $value = '?') {
        $this->sql .= ' WHERE ' . $this->createRange($column_name, $compare, $value);
        return $this;
    }

    /**
     * and条件 接在where后
     * @param string $column_name   列名
     * @param string $compare       待执行操作，支持in like，between只支持占位符（参数三无用）
     * @param string $value         值，默认占位符?
     * @return $this
     */
    public function and(string $column_name, string $compare, string $value = '?') {
        $this->sql .= ' AND ' . $this->createRange($column_name, $compare, $value);
        return $this;
    }

    /**
     * or条件 接在where后
     * @param string $column_name   列名
     * @param string $compare       待执行操作，支持in like，between只支持占位符（参数三无用）
     * @param string $value         值，默认占位符?
     * @return $this
     */
    public function or(string $column_name, string $compare, string $value = '?') {
        $this->sql .= ' OR '. $this->createRange($column_name, $compare, $value);
        return $this;
    }

    /**
     * limit column_num offset offset_num
     * @param int $num
     * @param int $offset
     * @return $this
     */
    public function limit(int $num = 1000, int $offset = 0) {
        $this->sql .= ' LIMIT ' . $num . ' OFFSET ' . $offset;
        return $this;
    }

    /**
     * order by column_name ase/dese
     * @param $columns
     * @return $this
     */
    public function orderBy($columns) {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $params = [];
        $types = ['ASC', 'DESC'];
        foreach ($columns as $column => $type) {
            $str = strtoupper($type);
            if (in_array($str, $types)) {
                array_push($params, '`' . $column . '` ' . $str);
            }else {
                array_push($params, '`' . $type . '` ASC');
            }
        }
        $this->sql .= ' ORDER BY ' . implode(', ', $params);
        var_dump($this->sql);

        return $this;
    }

    /**
     * having ***
     * @param string $sql  太复杂，不想解释
     * @return $this
     */
    public function having(string $sql) {
        $this->sql .= ' HAVING ' . $sql;
        return $this;
    }

    /**
     * group by name_list
     * 合计函数使用
     * @param $column_name
     * @return $this
     */
    public function groupBy($column_name) {
        if (!is_array($column_name)) {
            $column_name = [$column_name];
        }
        $this->sql .= ' GROUP BY `' . implode('`, `', $column_name) .  '`';
        return $this;
    }

    /**
     * 追加点sql。或者说，对没有设置的语法的补充
     * 比如 select * from table1 where uid = (select uid from table2 where email != 'kasukuikawai@mgail.com')
     *      where之后以上方法，几乎都不能实现（包括嵌套）
     *      现在可以：
     *      $db->select('*', 'table1')
     *          ->other('WHERE UID = (')
     *          ->select('uid', 'table2')
     *          ->where('email', '!=' , 'kasukuikawai@gmail.com')
     *          ->other(')');
     * @param string $append
     */
    public function other(string $append) {
        $this->sql .= ' ' . $append;
    }

    /**
     * 提交数据，不能绑定数据
     * @return $this
     */
    public function exec() {
        try {
            $this->prepare();
            $this->handle->beginTransaction();
            $this->stmt->execute();
            $this->handle->commit();
            $this->reset();
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
        return $this;
    }

    /**
     * 绑定一行的数据，并提交
     * @param array $value
     * @return $this
     */
    public function bind(array $value) {
        try {
            $this->prepare();
            $this->handle->beginTransaction();
            $this->stmt->execute($value);
            $this->handle->commit();
            $this->reset();
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
            $this->handle->rollBack();
        }
        return $this;
    }

    /**
     * 绑定多行数据，并提交
     * @param array $rows
     * @return $this
     */
    public function bindRows(array $rows) {
        try {
            $this->prepare();
            $this->handle->beginTransaction();
            foreach ($rows as $row) {
                $this->stmt->execute($row);
            }
            $this->handle->commit();
            $this->reset();
        }catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
            $this->handle->rollBack();
        }
        return $this;
    }

    /**
     * 返回下一行数据
     * @return mixed
     */
    public function fetch() {
        return $this->stmt->fetch();
    }

    /**
     * 返回所有数据
     * @return mixed
     */
    public function fetchALL() {
        return $this->stmt->fetchAll();
    }

    /**
     * 返回某一行数据，不存在返回[]
     * @param int $row
     * @return array
     */
    public function fetchOne(int $row = 1) {
        $fetch = $this->fetchALL();
        $count = count($fetch);
        if ($row <= $count && $row > 0) {
            return $fetch[$row - 1];
        }
        return [];
    }

    /**
     * 返回下一行的某列数据
     * @param int $column
     * @return mixed
     */
    public function fetchColumn(int $column = 0) {
        return $this->stmt->fetchColumn($column);
    }

    /**
     * 影响行数
     * @return int
     */
    public function rowCount(): int {
        return $this->stmt->rowCount();
    }

    /**
     * 获取自动增长键，最后一个id
     * ***仅插入时有用***
     * @return int
     */
    public function lastInsertId(): int {
        return $this->handle->lastInsertId();
    }

    /**
     * 同PDOStatement::prepare
     */
    protected function prepare(){
        try {
            if ($this->flag === false) {
                $this->stmt = $this->handle->prepare($this->sql);
                $this->flag = true;
            }
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * 生成where, and, or之后的条件
     * @param string $column_name
     * @param string $compare       支持in not in like | between只支持占位符，第三个参数不存在
     * @param string $value
     * @return string
     */
    protected function createRange(string $column_name, string $compare, string $value = '?') {
        $str = '`' . $column_name . '` ';

        //区别占位符
        if ($value !== '?') {
            $value = '\'' . $value . '\'';
        }

        $array = ['IN', 'LIKE', 'NOT IN'];
        if (in_array(strtoupper($compare), $array)) {
            $str .= strtoupper($compare) . ' (' . $value . ')';
        }elseif (strtoupper($compare) === 'BETWEEN') {
            $str .= 'BETWEEN ? AND ?';
        }else {
            $str .= $compare . ' ' . $value;
        }

        return $str;
    }

    /**
     * 重置
     * 在query, exec, bind, bindColumns运行内部调用
     */
    protected function reset() {
        $this->flag = false;
        $this->sql_copy = $this->sql;
        $this->sql = '';
    }

    /**
     * 设置数据库连接时几个参数
     * @param array $config
     */
    protected function setConfig(array $config) {
        foreach ($config as $key => $value) {
            if ($this->hasProperty($key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @return string
     */
    public function getDbName(): string {
        return $this->dbName;
    }

    /**
     * @param string $dbName
     */
    public function setDbName(string $dbName) {
        $this->dbName = $dbName;
    }

    /**
     * @return string
     */
    public function getUser(): string {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user) {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getPassword(): string {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password) {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getHost(): string {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host) {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getSql(): string {
        if (empty($this->sql)) {
            return $this->sql_copy;
        }
        return $this->sql;
    }
}