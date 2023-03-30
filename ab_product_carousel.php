<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

class Ab_Product_Carousel extends Module
{
    public function __construct()
    {
        $this->name = 'ab_product_carousel';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Aleksander';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('AB Product Carousel');
        $this->description = $this->l('Displays a carousel of products on the home page.');

        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() &&
            Configuration::updateValue('AB_PRODUCT_CAROUSEL_CATEGORIES', '') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            $categories = array();
            $categories[] = (int)Tools::getValue('category_block_1');
            $categories[] = (int)Tools::getValue('category_block_2');
            $categories[] = (int)Tools::getValue('category_block_3');
            Configuration::updateValue('AB_PRODUCT_CAROUSEL_CATEGORIES', implode(',', $categories));
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output.$this->renderForm();
    }

    
    public function renderForm() {

            // Retrieve categories
            $categories = Category::getCategories(Context::getContext()->language->id, true, false);
            unset($categories[0]);
            // Generate select options for categories
            $categoryOptions = array();
            foreach ($categories as $category) {
                $categoryOptions[] = array(
                    'id' => $category['id_category'],
                    'name' => $category['name']   
                );
            }

            $categories_get = [];
            if ( Configuration::get('AB_PRODUCT_CAROUSEL_CATEGORIES') ) {
                $categories_get = explode(",", Configuration::get('AB_PRODUCT_CAROUSEL_CATEGORIES'));
            } else {
                $categories_get[0] = $categories[1];
                $categories_get[1] = $categories[2];
                $categories_get[2] = $categories[3];
            }

            // Build form fields
            $fieldsForm = array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('AB Product Carousel Configuration'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => array(
                        array(
                            'type' => 'select',
                            'label' => $this->l('Category Block 1'),
                            'name' => 'category_block_1',
                            'options' => array(
                                'query' => $categoryOptions,
                                'id' => 'id',
                                'name' => 'name',
                            ),
                        ),
                        array(
                            'type' => 'select',
                            'label' => $this->l('Category Block 2'),
                            'name' => 'category_block_2',
                            'options' => array(
                                'query' => $categoryOptions,
                                'id' => 'id',
                                'name' => 'name',
                            ),
                        ),
                        array(
                            'type' => 'select',
                            'label' => $this->l('Category Block 3'),
                            'name' => 'category_block_3',
                            'options' => array(
                                'query' => $categoryOptions,
                                'id' => 'id',
                                'name' => 'name',
                            ),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            );

            // Build form
            
            $helper = new HelperForm();
            $helper->show_toolbar = false;
            $helper->table = $this->table;
            $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
            $helper->default_form_language = $lang->id;
            $helper->module = $this;
            $helper->allow_employee_form_lang = $lang->id;
            $helper->identifier = $this->identifier;
            $helper->submit_action = 'submit' . $this->name;
            $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->tpl_vars = array(
                'fields_value' => [
                    'category_block_1' => $categories_get[0],
                    'category_block_2' => $categories_get[1],
                    'category_block_3' => $categories_get[2]
                ],
            );

            return $helper->generateForm(array($fieldsForm));
    }

    public function hookDisplayHeader()
    {
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index') {
            // Register CSS and JS files
            $this->context->controller->registerStylesheet(
                'tiny-slider-css',
                'modules/'.$this->name.'/views/css/tiny-slider.css',
                array('media' => 'all', 'priority' => 150)
            );
            $this->context->controller->registerStylesheet(
                'modules-ab_product_carousel',
                'modules/'.$this->name.'/views/css/ab_product_carousel.css',
                array('media' => 'all', 'priority' => 200)
            );

            $this->context->controller->registerJavascript(
                'tiny-slider',
                'modules/'.$this->name.'/views/js/tiny-slider.js',
                array('priority' => 150)
            );
            $this->context->controller->registerJavascript(
                'modules-ab_product_carousel',
                'modules/'.$this->name.'/views/js/ab_product_carousel.js',
                array('priority' => 200)
            );
        }

    }

    public function hookDisplayHome()
    {
        $config = Configuration::get('AB_PRODUCT_CAROUSEL_CATEGORIES');
        $products = [];
        if ( $config  ) {
            $categories_get = explode(",", Configuration::get('AB_PRODUCT_CAROUSEL_CATEGORIES'));
            foreach ($categories_get as $key=>$category) {
                $products[$key]['products'] = $this->getProducts( (int) $category);
                $products[$key]['category_name'] = $this->getCategoryName( (int) $category);
                $products[$key]['category_url'] = $this->context->link->getCategoryLink($category);
            }

            // Render carousel template
            $this->context->smarty->assign(array(
                'products' => $products,
            ));
        }


        return $this->display(__FILE__, 'views/templates/hook/product_carousel.tpl');
    }

    protected function getProducts( int $id )
    {

        $results = $this->getProductsCaruzel($id,$this->context->language->id,1,10, 'position','ASC');



        $assembler = new ProductAssembler($this->context);

        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $products_for_template = [];

        foreach ($results as $rawProduct) {

            $products_for_template[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($rawProduct),
                $this->context->language
            );
        }

        return $products_for_template;
    }

    /**
     * Returns category name.
     *
     * @param int $id ID category

     */
    protected function getCategoryName( int $id ) {

        return Db::getInstance()->getValue('SELECT `name` FROM ' . _DB_PREFIX_ . 'category_lang  WHERE `id_category` = '.$id.' AND `id_lang` = '.$this->context->language->id.' AND `id_shop`= '.$this->context->shop->id);

    }

         /**
     * Returns category products.
     *
     * @param int $id ID category
     * @param int $idLang Language ID
     * @param int $p Page number
     * @param int $n Number of products per page
     * @param string|null $orderyBy ORDER BY column
     * @param string|null $orderWay Order way
     * @param bool $getTotal If set to true, returns the total number of results only
     * @param bool $active If set to true, finds only active products
     * @param bool $nozero If set to true, finds only products quantity > 0
     * @param bool $random If true, sets a random filter for returned products
     * @param int $randomNumberProducts Number of products to return if random is activated
     * @param bool $checkAccess If set to `true`, check if the current customer
     *                          can see the products from this category
     * @param Context|null $context Instance of Context
     *
     * @return array|int|false Products, number of products or false (no access)
     *
     * @throws PrestaShopDatabaseException
     */
    protected function getProductsCaruzel(
        $id,
        $idLang,
        $p,
        $n,
        $orderyBy = null,
        $orderWay = null,
        $getTotal = false,
        $active = true,
        $nozero = true,
        $random = false,
        $randomNumberProducts = 1,
        $checkAccess = false,
        Context $context = null
    ) {
        if (!$context) {
            $context = Context::getContext();
        }

        if ($checkAccess && !$this->checkAccess($context->customer->id)) {
            return false;
        }

        

        $front = in_array($context->controller->controller_type, array('front', 'modulefront'));
        $idSupplier = (int) Tools::getValue('id_supplier');
        
        if ($getTotal) {
            $sql = 'SELECT COUNT(cp.`id_product`) AS total
					FROM `' . _DB_PREFIX_ . 'product` p
					' . Shop::addSqlAssociation('product', 'p') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.`id_product` = cp.`id_product`
					WHERE cp.`id_category` = ' . (int) $id .
                ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') .
                ($active ? ' AND product_shop.`active` = 1' : '') .
                ($idSupplier ? ' AND p.id_supplier = ' . (int) $idSupplier : '');
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }
        if ($p < 1) {
            $p = 1;
        }
        
        $orderyBy = Validate::isOrderBy($orderyBy) ? Tools::strtolower($orderyBy) : 'position';
        $orderWay = Validate::isOrderWay($orderWay) ? Tools::strtoupper($orderWay) : 'ASC';

        $orderByPrefix = false;
        if ($orderyBy == 'id_product' || $orderyBy == 'date_add' || $orderyBy == 'date_upd') {
            $orderByPrefix = 'p';
        } elseif ($orderyBy == 'name') {
            $orderByPrefix = 'pl';
        } elseif ($orderyBy == 'manufacturer' || $orderyBy == 'manufacturer_name') {
            $orderByPrefix = 'm';
            $orderyBy = 'name';
        } elseif ($orderyBy == 'position') {
            $orderByPrefix = 'cp';
        }
        if ($orderyBy == 'price') {
            $orderyBy = 'orderprice';
        }
        $nbDaysNewProduct = Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
        if (!Validate::isUnsignedInt($nbDaysNewProduct)) {
            $nbDaysNewProduct = 20;
        }

        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) AS quantity' . (Combination::isFeatureActive() ? ', IFNULL(product_attribute_shop.id_product_attribute, 0) AS id_product_attribute,
					product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity' : '') . ', pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, image_shop.`id_image` id_image,
					il.`legend` as legend, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00",
					INTERVAL ' . (int) $nbDaysNewProduct . ' DAY)) > 0 AS new, product_shop.price AS orderprice
				FROM `' . _DB_PREFIX_ . 'category_product` cp
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p
					ON p.`id_product` = cp.`id_product`
				' . Shop::addSqlAssociation('product', 'p') .
                (Combination::isFeatureActive() ? ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
				ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')' : '') . '
				' . Product::sqlStock('p', 0) . '
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = ' . (int) $idLang . Shop::addSqlRestrictionOnLang('cl') . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . (int) $idLang . Shop::addSqlRestrictionOnLang('pl') . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = ' . (int) $idLang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'stock_available` sa
					ON (sa.`id_product_attribute` = p.`cache_default_attribute` AND p.`id_product` = sa.`id_product`)
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE product_shop.`id_shop` = ' . (int) $context->shop->id . '
					AND cp.`id_category` = ' . (int) $id
                    . ($active ? ' AND product_shop.`active` = 1' : '')
                    . ($nozero ? ' AND sa.`quantity` > 0' : '')
                    . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '')
                    . ($idSupplier ? ' AND p.id_supplier = ' . (int) $idSupplier : '');

       

        if ($random === true) {
            $sql .= ' ORDER BY RAND() LIMIT ' . (int) $randomNumberProducts;
        } else {
            $sql .= ' ORDER BY ' . (!empty($orderByPrefix) ? $orderByPrefix . '.' : '') . '`' . bqSQL($orderyBy) . '` ' . pSQL($orderWay) . '
			LIMIT ' . (((int) $p - 1) * (int) $n) . ',' . (int) $n;
        }


        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);


        if (!$result) {
            return array();
        }
        if ($orderyBy == 'orderprice') {
            Tools::orderbyPrice($result, $orderWay);
        }
        return Product::getProductsProperties($idLang, $result);
    }

}