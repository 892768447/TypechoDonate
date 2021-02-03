<?php

/**
 * 用户捐赠设置
 *
 * @package TypechoDonate
 * @author Irony
 * @email 892768447@qq.com
 * @version 0.0.1
 * @link https://github.com/892768447/TypechoDonate
 *
 */

class TypechoDonate_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return string
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // 创建数据库字段
        $info = TypechoDonate_Plugin::addTable();

        // 设置插件选项中的_plugin:TypechoDonate为空
        // 防止修改信息的时候被读取并设置输入框为空白数据
        $db = Typecho_Db::get();
        $name = '_plugin:TypechoDonate';
        $widget = Typecho_Widget::widget('Widget_Abstract_Options');

        if ($db->fetchObject($db->select(array('COUNT(*)' => 'num'))
            ->from('table.options')->where('name = ? AND user = ?', $name, 0))->num > 0) {
            $widget->update(array('value' => serialize(array())), $db->sql()->where('name = ? AND user = ?', $name, 0));
        } else {
            $widget->insert(array(
                'name'  => $name,
                'value' => serialize(array()),
                'user'  => 0
            ));
        }
        return _t($info);
    }
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
    }
    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }
    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        $db = Typecho_Db::get();
        $user = Typecho_Widget::widget('Widget_User');
        $user->execute();
        $result = $db->fetchRow($db->select('alipay', 'wxpay')->from('table.users')->where('uid = ?', $user->uid));

        $alipay = new Typecho_Widget_Helper_Form_Element_Text('alipay', NULL, $result['alipay'], _t('打赏中使用的支付宝二维码,建议尺寸小于250×250,且为正方形'));
        $wxpay = new Typecho_Widget_Helper_Form_Element_Text('wxpay', NULL, $result['wxpay'], _t('打赏中使用的微信二维码,建议尺寸小于250×250,且为正方形'));
        $form->addInput($alipay);
        $form->addInput($wxpay);

        $alipay->addRule('url', _t('支付宝赞赏二维码图片地址格式错误'));
        $wxpay->addRule('url', _t('微信赞赏二维码图片地址格式错误'));
        $alipay->addRule('maxLength', _t('最多包含255个字符'), 255);
        $wxpay->addRule('maxLength', _t('最多包含255个字符'), 255);
    }
    /**
     * 个人用户的配置面板数据存入用户字段
     *
     * @access public
     * @param array $settings 配置值
     * @param bool $isSetup 是否设置
     * @return void
     */
    public function personalConfigHandle($settings, $isSetup)
    {
        $db = Typecho_Db::get();
        $user = Typecho_Widget::widget('Widget_User');
        $user->execute();
        $db->query($db->sql()->where('uid = ?', $user->uid)->update('table.users')->rows(
            array(
                'alipay' => Typecho_Common::removeXSS($settings['alipay']),
                'wxpay' => Typecho_Common::removeXSS($settings['wxpay'])
            )
        ));
    }
    /**
     * 创建数据库字段
     *
     * @throws Typecho_Db_Exception
     */
    private static function addTable()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        // 查找是否已经安装
        try {
            $db->query($db->select('table.users.alipay', 'table.users.wxpay')->from('table.users'));
            return 'TypechoDonate插件启用成功';
        } catch (Typecho_Db_Exception $e) {
        }
        try {
            $sql = "ALTER TABLE `" . $prefix . "users` ADD `alipay` VARCHAR(255)  DEFAULT '';";
            $db->query($sql);
        } catch (Typecho_Db_Exception $e) {
        }
        try {
            $sql = "ALTER TABLE `" . $prefix . "users` ADD `wxpay` VARCHAR(255)  DEFAULT '';";
            $db->query($sql);
        } catch (Typecho_Db_Exception $e) {
        }
        //检测是否创建字段成功
        $db->query($db->select('table.users.alipay', 'table.users.wxpay')->from('table.users'));
        return 'TypechoDonate插件启用成功';
    }
}
