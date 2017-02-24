<?php
/**
 * Author: Alrash
 * Date: 2017/01/21 22:13
 * Description: 模型父类
 */

namespace Aria\base;

use Aria\Aria;
use Aria\verification\Verification;

class Model extends Component implements ModelInterface {
    use SetMethodLimitTrait;

    //错误提示
    private $errorMessage = [];

    /**
     * 数据检验规则：
     * （较yii2.0 验证仿写？改编？   仅看过魏曦的视频，此部分未拜见源码）
     *
     * 总规则书写形式：
     *  [
     *      '场景一' => [],
     *      '场景二' => [],
     *      'self:default_scenario/不写' => [],
     *  ];
     *
     * 场景规则（优先度无，尽量不要写含优先度的规则吧）：
     *  [
     *      [['name1', ... ] / 'name1', 'required'],                需要这些属性有值
     *      [['name1', 'name2', ...], 'union'],                     这些属性中只能有一个
     * --------------------------------------------------------------------------------------------------------------
     *      ['name1', 'id'],                                        纯数字，范围由id_range(common/config/setting.php)指定
     *      ['name1', 'email'],                                     email
     *      ['name1', 'string'],                                    是字符串？
     *      ['name1', 'int/integer'],                               是数字？
     *      ['name1', 'boolean'],                                   是布尔值？
     *      ['name1', 'float'],                                     是浮点数？
     *      ['name1', 'regex', 'exp?'],                             检测exp所表示的正则表达式
     *      比较name1的值和value的关系（写面列出），也可以是和某一属性比较，需要'data:'标记[关系1, 对象1, 关系2, 对象2, ...]
     *      ['name1', 'compare', ['> / >= / == / === / != / !== / <= / < / <>', 'value' / 'data:name2', ...]],
     * ---------------------------------------------------------------------------------------------------------------
     *      (可选参数)
     *      以上均支持'length' => [6, 10] / [6] / ['max' => 10, 'min' => 6](指定一个)     长度
     *               'compare' => [同compare]                                           同前
     *               'min' => 6,                                                        数字最小值
     *               'max' => 3333333333333,                                            数字最大值
     * ---------------------------------------------------------------------------------------------------------------
     *      调用其他类方法检测（注意该类需能自动获取请求数据或者含有load/setData方法）
     *      ['name1', targetClass => 'className(包括完整命名空间)', method => '方法名'(, scenario => '' / [外类使用场景]), param => ''],
     *
     *      以上均可选'message' => 'error'                                               错误信息
     *      ...
     *  ]
     * @return array
     * */
    public function rules() {
        return [];
    }

    /**
     * 载入数据（先验证规则）
     * @param array $data 数据
     * @param string $scenario 数据使用场景
     * @return bool
     */
    final public function load(array $data, $scenario = Verification::default_scenario) {
        $verify = new Verification(Aria::$app->message['verificationError']);
        $result = $verify->verifyRules($this->rules(), $data, $scenario);
        $this->setErrorMessage($verify->getErrorMessage());
        foreach ($data as $key => $value) {
            $setter = 'set' . $key;
            if ($this->hasMethod($setter)) {
                $this->$setter($value);
            }
        }
        return $result;
    }

    /**
     * 替换错误提示
     * 如：'id' => 'id需要****'
     *
     * @return array
     * */
    protected function replaceMessage() {
        return [];
    }

    /**
     * 替换错误信息key
     * 如：
     * [
     *    'id' => 'id_error',
     * ]
     * 则，获取errorMessage时，id所指代的值，将变换为id_error指向
     * @return array
     */
    protected function replaceMessageKey(){
        return [];
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getErrorMessage(): array {
        $replace = array_intersect_ukey($this->replaceMessageKey(), $this->errorMessage);
        foreach ($replace as $origin_key => $target_key) {
            $this->errorMessage[$target_key] = $this->errorMessage[$origin_key];
            unset($this->errorMessage[$origin_key]);
        }
        return $this->errorMessage;
    }

    /**
     * 在检验规则之后，设置错误信息
     * @param array $errorMessage
     */
    protected function setErrorMessage(array $errorMessage) {
        $replaceMessage = $this->replaceMessage();
        if ($replaceMessage !== [] && $errorMessage !== []) {
            foreach ($replaceMessage as $property => $message) {
                if (array_key_exists($property, $errorMessage)) {
                    $errorMessage[$property] = $message;
                }
            }
        }
        $this->errorMessage = $errorMessage;
    }
}