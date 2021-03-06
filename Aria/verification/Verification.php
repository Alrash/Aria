<?php
/**
 * Author: Alrash
 * Date: 2017/01/21 22:32
 * Description: 基本验证类
 */

namespace Aria\verification;

use Aria\algorithm\HeapSort;
use Aria\Aria;
use Aria\base\Component;
use Aria\base\LogicException;
use Aria\base\MissingException;
use Aria\base\ParamException;
use Aria\base\SetMethodLimitTrait;
use Aria\stack\RepeatableStack;

/**
 * Class Verification
 * @package Aria\base
 */
class Verification extends Component implements VerifyRulesInterface {
    use SetMethodLimitTrait;

    /**
     * 默认场景常量定义
     */
    const default_scenario = 'SCENARIO_ALL';
    const lost_scenario = self::default_scenario;

    /**
     * 内部优先级定义
     */
    const level_required = 1000;
    const level_union = 500;
    const level_id = 100;
    const level_normal = 0;

    //堆排序用
    const keyName = 'rule_level';

    /**
     * 其余定义
     */
    const default_min = PHP_INT_MIN;
    const default_max = PHP_INT_MAX;
    const default_length_max = 1000;

    private $errorMessage = [];
    private $messageArray = [];
    private $lang;

    public static $stack = null;

    /**
     * 必要规则定义
     * @var array
     */
    private $default_required_rule = [
        self::keyName => self::level_required,          //'rule_level' => 优先级
        'checkObject' => null,
        'targetClass' => [__CLASS__],
        'method' => ['required'],
        'params' => [[]],
        'errorMessage' => null,
    ];

    /**
     * 唯一规则定义
     * @var array
     */
    private $default_union_rule = [
        self::keyName => self::level_union,
        'checkObject' => null,
        'targetClass' => [__CLASS__],
        'method' => ['union'],
        'params' => [[]],
        'errorMessage' => null,
    ];

    /**
     * id规则定义
     * @var array
     */
    private $default_id_rule = [
        self::keyName => self::level_id,
        'checkObject' => null,
        'targetClass' => [__CLASS__],
        'method' => ['checkId'],
        'params' => [[id_range]],
        'errorMessage' => null,
    ];

    /**
     * 通用规则定义
     * @var array
     */
    private $default_normal_rule = [
        self::keyName => self::level_normal,
        'checkObject' => null,
        'targetClass' => [__CLASS__],
        'method' => [],
        'params' => [[]],
        'scenario' => '',
        'min' => self::default_min,
        'max' => self::default_max,
        'errorMessage' => null,
    ];

    private $rules;
    private $data = [];
    private $unions = [];

    /**
     * Verification constructor.
     * 默认错误语言使用环境中的语言定义，暂时只有中简和英语两种
     * @param array $config
     * @param array $params
     */
    public function __construct(array $config = [], array $params = ['language' => APP_LANG,]) {
        parent::__construct($config, $params);
    }

    /**
     * 初始化类环境
     * @param array $config
     * @param array $params
     */
    public function init(array $config = [], array $params = []) {
        parent::init($config, $params); // TODO: Change the autogenerated stub

        //使用堆排，创建规则序列
        $this->rules = new HeapSort(true, self::keyName);
        //初始化语言环境
        $this->lang = isset($this->lang) ? $this->lang : APP_LANG;
        //类似单例模式，创建唯一栈
        if (!isset(self::$stack)) {
            self::$stack = new RepeatableStack(RepeatableStack::level_unique);
        }
    }

    /**
     * 重载参数映射
     * @return array
     */
    public function paramsMap() {
        return [
            'config' => 'messageArray',
            'language' => 'lang',
        ];
    }

    /**
     * 规则验证接口
     * @param array $rules      所有规则
     * @param array $data       已有数据
     * @param $scenario         使用场景
     * @return bool             完全正确true，一点错false
     * @throws LogicException
     */
    public function verifyRules(array $rules, array $data, $scenario): bool {
        // TODO: Implement verifyRules() method.

        //规则为空，直接返回
        if ($rules === [])
            return true;

        //提取并调整规则
        $this->adjustRules($this->collectRules($rules, $scenario));

        //若规则为空，则返回true
        if ($this->rules->isEmpty())
            return true;

        $this->setData($data);

        //验证规则
        do {
            //提取队顶规则
            $rule = $this->rules->top();
            $this->rules->pop();

            foreach ($rule['targetClass'] as $key => $class) {
                /*
                 * 下面逻辑设计时，留下的锅
                 * 比如，验证id时，不只需要验证是纯数字，可能要需要验证是大于100的数字
                 *      则，本设计需要两步验证，先调用本类的checkId方法，再调用本类的compare方法
                 * */
                $method = $rule['method'][$key];
                $params = $rule['params'][$key];
                $runScenario = $rule['scenario'];
                $result = false;

                if ($class === __CLASS__) {
                    //类内部调用
                    if (is_callable([$class, $method])) {
                        $result = call_user_func_array([$class, $method], $params);
                    }
                } else {
                    /*
                     * 类外部调用
                     * 使用ReflectionClass类构建
                     *  注：
                     *      (1) 不许调用单例模式类
                     *      (2) 调用方法类中含有load或setData方法时，先调用这两个方法填充数据
                     *      (3) 未防止死链（产生情况：调用其余的model对象，rules可能含有一个正在检测的model子类，
                     *                  导致重复调用，形成循环调用），使用静态唯一栈检测
                     **/
                    if (self::$stack->push([$class, $method, $params]) === false) {
                        throw new LogicException('Too much calling the same method ' . $class . '::' . $method . '!');
                    }

                    /*
                     * 你说不是有现成的CallbackTrait吗？下面多一部load，和一些小东西，所以直接重写了
                     * */
                    $classRF = new \ReflectionClass($class);
                    $trait = $classRF->getTraitNames();
                    //检测，不是单例模式的类，才可以使用
                    if (!$classRF->getConstructor()->isPublic()
                        || (!isset($trait)
                            && in_array(Aria::$app->singletonTraitName, $trait) || in_array(trim(Aria::$app->singletonTraitName, '\\'), $trait))
                    ) {
                        throw new LogicException('The callback reflection function does not support singleton pattern.');
                    }

                    $result = true;
                    $instance = $classRF->newInstance();

                    //载入数据
                    if ($classRF->hasMethod('load')) {
                        $methodRF = $classRF->getMethod('load');
                        if (!$methodRF->isPublic())
                            $methodRF->setAccessible(true);
                        $result = $methodRF->invokeArgs($instance, [$this->data, $runScenario]);
                    } elseif ($classRF->hasMethod('setData')) {
                        $methodRF = $classRF->getMethod('load');
                        if (!$methodRF->isPublic())
                            $methodRF->setAccessible(true);
                        $methodRF->invokeArgs($instance, [$this->data]);
                    }

                    //防止数据载入过程中出错（实际是load过程中再次调用规则验证，那时验证出错的情况）
                    if ($result === false) {
                        if ($classRF->hasMethod('getErrorMessage')) {
                            $this->errorMessage = $classRF->getMethod('getErrorMessage')->invoke($instance);
                        }
                        return false;
                    }

                    //调用目标方法
                    if ($classRF->hasMethod($method)) {
                        $methodRF = $classRF->getMethod($method);
                        if (!$methodRF->isPublic())
                            $methodRF->setAccessible(true);
                        $result = $methodRF->invokeArgs($instance, $params);
                    }

                    self::$stack->pop();
                }

                if ($result == false) {
                    $this->resetErrorMessage($rule['checkObject'], $rule['errorMessage'], 'union' === $method);
                    return false;
                }
            }

        } while (!$this->rules->isEmpty());
        return true;
    }

    /**
     * 获取使用场景的规则
     * 注：
     *  优先度: 暂无，若需多个，请分开写
     *      已注释//默认场景规则 < 具体场景规则
     *
     * @param array $rules      所有规则
     * @param $scenario         使用场景
     * @return array            需要的规则
     * @throws MissingException 缺少场景规则
     * */
    protected function collectRules(array $rules, $scenario) {
        $targetRules = [self::default_scenario => []];
        /*
         * 对场景的补充
         * 将场景变量补充为数组形式，增加默认/缺失场景，移除重复值
         * */
        if (!is_array($scenario)) {
            $scenario = array_unique([$scenario, self::default_scenario]);
        } else {
            array_push($scenario, self::default_scenario);
            $scenario = array_unique($scenario);
        }

        /*
         * 规则改写
         * 实际上是补全场景，加过滤不需要的场景规则
         * */
        foreach ($rules as $key => $rule) {
            if (!is_array($rule)) {
                $rule = [$rule];
            }

            if (is_int($key)) {
                $targetRules[self::default_scenario] = array_merge($targetRules[self::default_scenario], $rule);
            } elseif (array_search($key, $scenario, true) !== false) {
                if (!isset($targetRules[$key]))
                    $targetRules[$key] = [];

                $targetRules[$key] = array_merge($targetRules[$key], $rule);
            }
        }

        /*
         * 防止缺少场景
         * */
        if (($over = array_diff($scenario, array_keys($targetRules))) !== []) {
            $info = join(':', $over);
            throw new MissingException('Missing scenario: ' . $info . '.');
        }

        return $targetRules;
    }

    /**
     * 调整规则
     * 去除场景键，勉强调整优先级
     *
     * @param array $rules      提取的需要的规则
     * */
    private function adjustRules(array $rules) {

        foreach ($rules as $scenario_rules) {
            foreach ($scenario_rules as $rule) {
                if (!empty($rule)) {
                    $this->rules->push($this->adjustOneRule($rule));
                }
            }
        }
    }

    /**
     * 调整单条规则
     * 实际为向设计的规则填充数据
     *
     * @param array $rule
     * @return array
     */
    private function adjustOneRule(array $rule) {

        if (isset($rule[1])) {
            switch ($rule[1]) {
                case 'required':
                    $targetRule = $this->setAttributesFilling($rule, $this->default_required_rule, 'Required', null, true);
                    break;
                case 'union':
                    $targetRule = $this->setAttributesFilling($rule, $this->default_union_rule, 'Union', 'unionPush', true);
                    break;
                case 'id':
                    $targetRule = $this->setAttributesFilling($rule, $this->default_id_rule, 'id');
                    $targetRule = $this->setAttributes($rule, $targetRule);
                    break;
                default :
                    $targetRule = $this->setAttributesFilling($rule, $this->default_normal_rule, 'other');
                    $targetRule = $this->setAttributes($rule, $targetRule);
                    break;
            }
        }else {
            $targetRule = $this->setAttributesFilling($rule, $this->default_normal_rule, 'other');
            $targetRule = $this->setAttributes($rule, $targetRule);
        }

        return $targetRule;
    }

    /**
     * 填充规则
     * 填充需要验证的规则名、数据名、外部规则场景名（targetClass用）、错误提示
     *
     * @param array $rule
     * @param array $default_target_rule
     * @param $exceptionMessage
     * @param $call_func
     * @param bool $needArray
     * @return mixed
     * @throws MissingException
     */
    private function setAttributesFilling(array $rule, array $default_target_rule, $exceptionMessage, $call_func = null, bool $needArray = false) {
        $targetRule = $default_target_rule;

        if (isset($rule[0]) && $rule[0] !== []) {
            $targetRule['checkObject'] = $rule[0];
            if ($needArray && !is_array($rule[0]))
                $rule[0] = [$rule[0]];
        } else {
            throw new MissingException($exceptionMessage . ' rule misses property attribute or it is a [].');
        }

        array_unshift($targetRule['params'][0], $rule[0]);

        if (isset($call_func) && $this->hasMethod($call_func)) {
            call_user_func_array([__CLASS__, $call_func], [$rule[0]]);
        }

        //目标类使用
        if (isset($rule['scenario'])) {
            $targetRule['scenario'] = $rule['scenario'];
        } else {
            $targetRule['scenario'] = self::default_scenario;
        }

        if (isset($rule['message'])) {
            $targetRule['errorMessage'] = $rule['message'];
        }

        return $targetRule;
    }

    /**
     * 填充剩余规则
     *
     * @param $rule
     * @param $targetRule
     * @return mixed
     * @throws MissingException
     * @throws ParamException
     */
    private function setAttributes($rule, $targetRule) {
        /*
         * 处理[1]号位属性
         *  该属性为本类内方法
         *  方法为简写？形式，没有含有前缀，如check、is等
         * */
        if (isset($rule[1])) {
            $method = $rule[1];
            if (!is_string($method)) {
                throw new ParamException('Method needs string type!');
            }

            $flag = false;
            if ($this->hasMethod('check' . $method)) {
                $targetRule['targetClass'] = [__CLASS__];
                $targetRule['method'] = ['check' . ucfirst($method)];
                $flag = true;
            } elseif ($this->hasMethod('is' . $method)) {
                $targetRule['targetClass'] = [__CLASS__];
                $targetRule['method'] = ['is' . ucfirst($method)];
                $flag = true;
            } elseif ($this->hasMethod($method)) {
                $targetRule['targetClass'] = [__CLASS__];
                $targetRule['method'] = [$method];
                $flag = true;
            }

            /*
             * 自用方法补充参数
             * */
            if ($flag && isset($rule[2])) {
                array_push($targetRule['params'][0], $rule[2]);
            }
        }

        /*
         * 处理targetClass和method两个属性
         *  注：会覆盖$rule[1]所含方法
         * */
        if (isset($rule['targetClass'])) {
            if (is_string($rule['targetClass'])) {
                $targetRule['targetClass'] = [$rule['targetClass']];
            } else {
                throw new ParamException('The targetClass property needs string type!');
            }

            if (isset($rule['method'])) {
                $targetRule['method'] = [$rule['method']];
            } else {
                throw new MissingException('The ' . $targetRule['targetClass'] . ' class misses method!');
            }

            if (isset($rule['params'])) {
                if (is_array($rule['params'])) {
                    $targetRule['params'][0] = $rule['params'];
                } else {
                    $targetRule['params'][0] = [$rule['params']];
                }
            }
        }

        if (!isset($targetRule['targetClass']) || $targetRule['targetClass'] === []
            || !isset($targetRule['method']) || $targetRule['method'] === []
        ) {
            throw new MissingException(ucfirst($rule[0]) . ' rule misses targetClass or method property!');
        }

        /*
         * 设置range函数参数，并压入规则数组
         * */
        $targetRule['min'] = self::default_min;
        $targetRule['max'] = self::default_max;
        if (isset($rule['min'])) {
            if (is_int($rule['min'])) {
                $targetRule['min'] = max($rule['min'], $targetRule['min']);
            } else {
                throw new ParamException('The min property needs integer type');
            }
        }
        if (isset($rule['max'])) {
            if (is_int($rule['max'])) {
                $targetRule['max'] = min($rule['max'], $targetRule['max']);
            } else {
                throw new ParamException('The max property needs integer type');
            }
        }
        if ($targetRule['min'] !== self::default_min || $targetRule['max'] !== self::default_max) {
            array_push($targetRule['targetClass'], __CLASS__);
            array_push($targetRule['method'], 'range');
            array_push($targetRule['params'], [$rule[0], ['min' => $targetRule['min'], 'max' => $targetRule['max']]]);
        }
        unset($targetRule['min']);
        unset($targetRule['max']);

        if (isset($rule['compare'])) {
            if (is_array($rule['compare'])) {
                array_push($targetRule['targetClass'], __CLASS__);
                array_push($targetRule['method'], 'compare');
                array_push($targetRule['params'], [$rule[0], $rule['compare']]);
            } else {
                throw new ParamException('Rule compare property needs array type!');
            }
        }

        if (isset($rule['length'])) {
            if (is_array($rule['length'])) {
                $minDefaultLength = 0;
                $maxDefaultLength = self::default_length_max;

                if (isset($rule['length'][0])) {
                    $minLength = $rule['length'][0];
                } elseif (isset($rule['length']['min'])) {
                    $minLength = $rule['length']['min'];
                }
                if (isset($rule['length'][1])) {
                    $maxLength = $rule['length'][1];
                } elseif (isset($rule['length']['max'])) {
                    $maxLength = $rule['length']['max'];
                }
                $minLength = max($minLength, $minDefaultLength);
                $maxLength = min($maxLength, $maxDefaultLength);

                array_push($targetRule['targetClass'], __CLASS__);
                array_push($targetRule['method'], 'length');
                array_push($targetRule['params'], [$rule[0], $maxLength, $minLength]);
            } else {
                throw new ParamException('Rule length property needs array type!');
            }
        }

        return $targetRule;
    }

    /**
     * 已废
     * @param array $property
     */
    private function unionPush(array $property) {
        array_push($this->unions, $property);
    }

    /**
     * 检查需求的所有字段
     * @param array $params 待检测字段
     * @return true 所有字段都存在
     * */
    protected function required(array $params) {
        $result = true;
        foreach ($params as $param) {
            if (!array_key_exists($param, $this->data)) {
                $this->addErrorMessage($param, $this->messageArray['missing']['error'][$this->lang], true);
                $result = false;
            }
        }

        return $result;
    }

    /**
     * 检查唯一字段
     * @param array $params
     * @return boolean 只有一个字段存在
     */
    protected function union(array $params) {
        $count = 0;
        foreach ($params as $param) {
            if (array_key_exists($param, $this->data)) {
                $count++;
            }
        }

        if ($count === 1) {
            array_push($this->unions, $params);
            return true;
        } else {
            $this->addErrorMessage(join($params, ','), $this->messageArray['union'][$this->lang]);
            return false;
        }
    }

    protected function checkEmail($email) {
        if (!filter_var($this->data[$email], FILTER_VALIDATE_EMAIL)) {
            $this->addErrorMessage($email, $this->messageArray['format']['email'][$this->lang], true);
            return false;
        }
        return true;
    }

    protected function checkId($id, $range) {
        if (!filter_var($this->data[$id], FILTER_VALIDATE_INT, ['option' => $range])) {
            $this->addErrorMessage($id, $this->messageArray['format']['id'][$this->lang], true);
            return false;
        }
        return true;
    }

    /**
     * 规则中min和max两个属性使用
     * @param string $valueName
     * @param array $limit
     * @return bool
     * @throws ParamException $valueName不为字符串时，提示需要字符串类型数据 之后的偷懒没检测-_-|||
     */
    protected function range($valueName, array $limit = ['min' => self::default_min, 'max' => self::default_max]) {
        if (!is_string($valueName)) {
            throw new ParamException('The parameter $valueName of range method needs string type!');
        }

        if (filter_var($this->data[$valueName], FILTER_VALIDATE_INT,
            ['option' => ['min_range' => $limit['min'], 'max_range' => $limit['max']]])) {
            $this->addErrorMessage($valueName, $this->messageArray['range']['error'][$this->lang], true);
            return false;
        }
        return true;
    }

    protected function compare($valueName, array $describe) {
        $data = $this->data[$valueName];
        $length = count($describe);
        $result = false;

        if ($length % 2) {
            throw new ParamException('The parameter $describe of compare method in Verification class is wrong!');
        }

        for ($pos = 0; $pos < $length; $pos += 2) {
            $compareData = $this->extractCompareData($describe[$pos + 1]);
            switch ($describe[$pos]) {
                case '==':
                    $result = $data == $compareData;
                    break;
                case '===':
                    $result = $data === $compareData;
                    break;
                case '!=':
                    $result = $data != $compareData;
                    break;
                case '<>':
                    $result = $data <> $compareData;
                    break;
                case '!==':
                    $result = $data !== $compareData;
                    break;
                case '<':
                    $result = $data < $compareData;
                    break;
                case '>':
                    $result = $data > $compareData;
                    break;
                case '<=':
                    $result = $data <= $compareData;
                    break;
                case '>=':
                    $result = $data >= $compareData;
                    break;
                default:
                    $result = false;
                    break;
            }
            if ($result === false) {
                $this->addErrorMessage($valueName, $valueName . ' ' . $describe[$pos + 1] . $this->messageArray['compare'][$this->lang], true);
                break;
            }
        }
        return $result;
    }

    protected function regex($valueName, $pattern) {
        if (preg_match_all($pattern, $valueName)) {
            return true;
        } else {
            $this->addErrorMessage($valueName, $this->messageArray['format']['regex'][$this->lang]);
            return false;
        }
    }

    protected function length($valueName, $max = self::default_length_max, $min = 0) {
        $len = strlen($this->data[$valueName]);
        if ($len >= $min && $len <= $max)
            return true;
        else {
            $this->addErrorMessage($valueName, $this->messageArray['range']['length'][$this->lang]);
            return false;
        }
    }

    protected function isString($valueName) {
        if (!is_string($this->data[$valueName])) {
            $this->addErrorMessage($valueName, $this->messageArray['format']['string'][$this->lang]);
            return false;
        }
        return true;
    }

    protected function isBoolean($valueName) {
        if (!is_bool($this->data[$valueName])) {
            $this->addErrorMessage($valueName, $this->messageArray['format']['boolean'][$this->lang]);
            return false;
        }
        return true;
    }

    protected function isInt($valueName) {
        if (!is_int($this->data[$valueName])) {
            $this->addErrorMessage($valueName, $this->messageArray['format']['int'][$this->lang]);
            return false;
        }
        return true;
    }

    protected function isInteger($valueName) {
        if (!is_int($this->data[$valueName])) {
            $this->addErrorMessage($valueName, $this->messageArray['format']['int'][$this->lang]);
            return false;
        }
        return true;
    }

    protected function isFloat($valueName) {
        if (!is_float($this->data[$valueName])) {
            $this->addErrorMessage($valueName, $this->messageArray['format']['float'][$this->lang]);
            return false;
        }
        return true;
    }

    /**
     * 提取compare属性中的待比较数据
     * @param $string
     * @return mixed
     */
    protected function extractCompareData($string) {
        $num = preg_match('/data:([a-z_].*)/i', $string, $valueName);
        if ($num === 1 && isset($this->data[$valueName[1]])) {
            return $this->data[$valueName[1]];
        } else {
            return $string;
        }
    }

    /**
     * 设置错误信息
     * @param $checkObject
     * @param $message
     * @param bool $complete
     */
    public function addErrorMessage($checkObject, $message, $complete = false) {
        if (is_array($message)) {
            $message = array_map('array_values', $message);
        } else {
            $message = [$message];
        }

        if (is_array($checkObject)) {
            $checkObject = array_map('array_values', $checkObject);
        } else {
            $checkObject = [$checkObject];
        }

        $diff = count($checkObject) - count($message);
        if ($diff > 0) {
            $message = array_merge($message, array_fill(count($message), $diff, end($message)));
        }

        foreach ($checkObject as $key => $objectName) {
            if ($complete === false) {
                $this->errorMessage[$objectName] = $objectName . $message[$key];
            } else {
                $this->errorMessage[$objectName] = $message[$key];
            }
        }
    }

    /**
     * 重新设置错误信息
     * @param $checkObject 检测对象
     * @param $message 错误信息
     * @param bool $isJoin 需要以id,email的形式提供键值
     */
    public function resetErrorMessage($checkObject, $message, $isJoin = false) {
        if (!isset($message) || $message === []) {
            return;
        } elseif (!is_array($message)) {
            $message = [$message];
        }

        $flag = false;
        $keys = array_keys($message);
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->errorMessage)) {
                $this->errorMessage[$key] = $message[$key];
                $flag = true;
            }
        }

        if ($flag === true || !isset($checkObject) || $checkObject === []) {
            return;
        }

        if (!is_array($checkObject)) {
            $checkObject = [$checkObject];
        }
        if ($isJoin) {
            $checkObject = [join($checkObject, ',')];
        }
        $diff = count($checkObject) - count($message);
        if ($diff > 0) {
            $message = array_merge($message, array_fill(count($message), $diff, end($message)));
        } elseif ($diff < 0) {
            while ($diff !== 0) {
                $diff++;
                array_pop($message);
            }
        }
        $message = array_combine($checkObject, $message);
        foreach ($this->errorMessage as $key => $value) {
            if (array_key_exists($key, $message)) {
                $this->errorMessage[$key] = $message[$key];
            }
        }
    }

    /**
     * @return array
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * @param mixed $lang
     */
    protected function setLang($lang) {
        $this->lang = $lang;
    }

    /**
     * @param array $messageArray
     */
    public function setMessageArray(array $messageArray) {
        $this->messageArray = $messageArray;
    }

    public function setData($data) {
        $this->data = $data;
    }
}