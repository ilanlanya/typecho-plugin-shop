<?php
/**
 *     TYPECHO 线上主题商店
 *
 * @package Shop
 * @author LiCxi
 * @version 1.0.0
 * @link https://lichaoxi.com
 *
 */
class Shop_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Helper::addPanel(3, 'Shop/shop.php', '主题商店', '查看线上主题', 'administrator');
        Helper::addAction('shop-plugin', 'Shop_Action');
        return _t('插件安装成功');
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
        Helper::removePanel(3, 'Shop/shop.php');
        Helper::removeAction('shop-plugin', 'Shop_Action');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {}

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {}

}
