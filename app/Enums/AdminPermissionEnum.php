<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 *
 * @method static DASHBOARD_VIEW()
 * @method static CATEGORY_VIEW()
 * @method static CATEGORY_EDIT()
 * @method static CATEGORY_DELETE()
 * @method static CATEGORY_CREATE()
 * @method static DELIVERY_ZONE_CREATE()
 * @method static DELIVERY_ZONE_EDIT()
 * @method static DELIVERY_ZONE_DELETE()
 * @method static BRAND_VIEW()
 * @method static BRAND_EDIT()
 * @method static BRAND_DELETE()
 * @method static BRAND_CREATE()
 * @method static SELLER_EDIT()
 * @method static SELLER_DELETE()
 * @method static SELLER_CREATE()
 * @method static SELLER_VIEW()
 * @method static ROLE_CREATE()
 * @method static ROLE_EDIT()
 * @method static ROLE_DELETE()
 * @method static ROLE_VIEW()
 * @method static ROLE_PERMISSIONS_VIEW()
 * @method static ROLE_PERMISSIONS_EDIT()
 * @method static TAX_CLASS_VIEW()
 * @method static TAX_CLASS_CREATE()
 * @method static TAX_CLASS_EDIT()
 * @method static TAX_CLASS_DELETE()
 * @method static SYSTEM_USER_CREATE()
 * @method static SYSTEM_USER_EDIT()
 * @method static SYSTEM_USER_DELETE()
 * @method static SYSTEM_USER_VIEW()
 * @method static FAQ_CREATE()
 * @method static FAQ_EDIT()
 * @method static FAQ_DELETE()
 * @method static BANNER_CREATE()
 * @method static BANNER_EDIT()
 * @method static BANNER_DELETE()
 * @method static BANNER_VIEW()
 * @method static FEATURED_SECTION_CREATE()
 * @method static FEATURED_SECTION_EDIT()
 * @method static FEATURED_SECTION_DELETE()
 * @method static FEATURED_SECTION_SORTING_MODIFY()
 * @method static FEATURED_SECTION_VIEW()
 * @method static FEATURED_SECTION_SORTING_VIEW()
 * @method static DELIVERY_BOY_EDIT()
 * @method static DELIVERY_BOY_DELETE()
 * @method static DELIVERY_BOY_VIEW()
 * @method static DELIVERY_BOY_EARNING_VIEW()
 * @method static DELIVERY_BOY_EARNING_PROCESS_PAYMENT()
 * @method static DELIVERY_BOY_CASH_COLLECTION_VIEW()
 * @method static DELIVERY_BOY_CASH_COLLECTION_PROCESS()
 * @method static DELIVERY_BOY_WITHDRAWAL_VIEW()
 * @method static DELIVERY_BOY_WITHDRAWAL_PROCESS()
 * @method static SELLER_WITHDRAWAL_VIEW()
 * @method static SELLER_WITHDRAWAL_PROCESS()
 * @method static COMMISSION_VIEW()
 * @method static COMMISSION_SETTLE()
 * @method static ORDER_VIEW()
 * @method static RETURN_VIEW()
 * @method static PRODUCT_VIEW()
 * @method static PRODUCT_CREATE()
 * @method static PRODUCT_EDIT()
 * @method static PRODUCT_DELETE()
 * @method static PRODUCT_STATUS_UPDATE()
 * @method static PRODUCT_FAQS_VIEW()
 * @method static PROMO_CREATE()
 * @method static PROMO_EDIT()
 * @method static PROMO_DELETE()
 * @method static PROMO_VIEW()
 * @method static NOTIFICATION_CREATE()
 * @method static NOTIFICATION_EDIT()
 * @method static NOTIFICATION_DELETE()
 * @method static NOTIFICATION_VIEW()
 * @method static STORE_VIEW()
 * @method static STORE_CREATE()
 * @method static STORE_EDIT()
 * @method static STORE_DELETE()
 * @method static STORE_VERIFY()
 * @method static CUSTOMER_VIEW()
 * @method static CUSTOMER_CREATE()
 * @method static CUSTOMER_EDIT()
 * @method static CUSTOMER_DELETE()
 * @method static CUSTOMER_EXPORT()
 * @method static REPORT_VIEW()
 * @method static REPORT_EXPORT()
 * @method static BLOG_VIEW()
 * @method static BLOG_CREATE()
 * @method static BLOG_EDIT()
 * @method static BLOG_DELETE()
 * @method static MENU_VIEW()
 * @method static MENU_CREATE()
 * @method static MENU_EDIT()
 * @method static MENU_DELETE()
 * @method static SUPPORT_TICKET_VIEW()
 * @method static SUPPORT_TICKET_EDIT()
 * @method static SUPPORT_TICKET_DELETE()
 * @method static PAGE_VIEW()
 * @method static PAGE_EDIT()
 * @method static ENQUIRY_VIEW()
 * @method static ENQUIRY_DELETE()
 * @method static PRODUCT_IMPORT_VIEW()
 * @method static PRODUCT_IMPORT_IMPORT()
 * @method static HOME_PAGE_VIEW()
 * @method static HOME_PAGE_EDIT()
 *
 * Module-wise setting permissions
 * @method static SETTING_SYSTEM_VIEW()
 * @method static SETTING_SYSTEM_EDIT()
 * @method static SETTING_STORAGE_VIEW()
 * @method static SETTING_STORAGE_EDIT()
 * @method static SETTING_EMAIL_VIEW()
 * @method static SETTING_EMAIL_EDIT()
 * @method static SETTING_PAYMENT_VIEW()
 * @method static SETTING_PAYMENT_EDIT()
 * @method static SETTING_AUTHENTICATION_VIEW()
 * @method static SETTING_AUTHENTICATION_EDIT()
 * @method static SETTING_NOTIFICATION_VIEW()
 * @method static SETTING_NOTIFICATION_EDIT()
 * @method static SETTING_WEB_VIEW()
 * @method static SETTING_WEB_EDIT()
 * @method static SETTING_APP_VIEW()
 * @method static SETTING_APP_EDIT()
 * @method static SETTING_DELIVERY_BOY_VIEW()
 * @method static SETTING_DELIVERY_BOY_EDIT()
 * @method static SETTING_HOME_GENERAL_SETTINGS_VIEW()
 * @method static SETTING_HOME_GENERAL_SETTINGS_EDIT()
 * @method static FAQ_VIEW()
 * @method static DELIVERY_ZONE_VIEW()
 */
enum AdminPermissionEnum: string
{

    use InvokableCases, Values, Names;

    case DASHBOARD_VIEW = 'dashboard.view';
    case CATEGORY_CREATE = 'category.create';
    case CATEGORY_EDIT = 'category.edit';
    case CATEGORY_DELETE = 'category.delete';
    case CATEGORY_VIEW = 'category.view';
    case BRAND_CREATE = 'brand.create';
    case BRAND_EDIT = 'brand.edit';
    case BRAND_DELETE = 'brand.delete';
    case BRAND_VIEW = 'brand.view';
    case SELLER_CREATE = 'seller.create';
    case SELLER_EDIT = 'seller.edit';
    case SELLER_DELETE = 'seller.delete';
    case SELLER_VIEW = 'seller.view';
    // Deprecated generic setting permissions (kept for backward compatibility, no longer used)
    case SETTING_VIEW_ALL = 'setting.view.all';
    case SETTING_VIEW = 'setting.view';
    case SETTING_EDIT = 'setting.edit';
    // Module-wise setting permissions
    case SETTING_SYSTEM_VIEW = 'setting.system.view';
    case SETTING_SYSTEM_EDIT = 'setting.system.edit';
    case SETTING_STORAGE_VIEW = 'setting.storage.view';
    case SETTING_STORAGE_EDIT = 'setting.storage.edit';
    case SETTING_EMAIL_VIEW = 'setting.email.view';
    case SETTING_EMAIL_EDIT = 'setting.email.edit';
    case SETTING_PAYMENT_VIEW = 'setting.payment.view';
    case SETTING_PAYMENT_EDIT = 'setting.payment.edit';
    case SETTING_AUTHENTICATION_VIEW = 'setting.authentication.view';
    case SETTING_AUTHENTICATION_EDIT = 'setting.authentication.edit';
    case SETTING_NOTIFICATION_VIEW = 'setting.notification.view';
    case SETTING_NOTIFICATION_EDIT = 'setting.notification.edit';
    case SETTING_WEB_VIEW = 'setting.web.view';
    case SETTING_WEB_EDIT = 'setting.web.edit';
    case SETTING_APP_VIEW = 'setting.app.view';
    case SETTING_APP_EDIT = 'setting.app.edit';
    case SETTING_DELIVERY_BOY_VIEW = 'setting.delivery_boy.view';
    case SETTING_DELIVERY_BOY_EDIT = 'setting.delivery_boy.edit';
    case SETTING_HOME_GENERAL_SETTINGS_VIEW = 'setting.home_general_settings.view';
    case SETTING_HOME_GENERAL_SETTINGS_EDIT = 'setting.home_general_settings.edit';
    case ROLE_CREATE = 'role.create';
    case ROLE_EDIT = 'role.edit';
    case ROLE_DELETE = 'role.delete';
    case ROLE_VIEW = 'role.view';
    case ROLE_PERMISSIONS_VIEW = 'role.permission.view';
    case ROLE_PERMISSIONS_EDIT = 'role.permission.edit';
    case TAX_CLASS_VIEW = 'tax_class.view';
    case TAX_CLASS_CREATE = 'tax_class.create';
    case TAX_CLASS_EDIT = 'tax_class.edit';
    case TAX_CLASS_DELETE = 'tax_class.delete';
    case SYSTEM_USER_CREATE = 'system_user.create';
    case SYSTEM_USER_EDIT = 'system_user.edit';
    case SYSTEM_USER_DELETE = 'system_user.delete';
    case SYSTEM_USER_VIEW = 'system_user.view';
    case FAQ_CREATE = 'faq.create';
    case FAQ_EDIT = 'faq.edit';
    case FAQ_DELETE = 'faq.delete';
    case FAQ_VIEW = 'faq.view';
    case BANNER_CREATE = 'banner.create';
    case BANNER_EDIT = 'banner.edit';
    case BANNER_DELETE = 'banner.delete';
    case BANNER_VIEW = 'banner.view';
    case DELIVERY_ZONE_CREATE = 'delivery_zone.create';
    case DELIVERY_ZONE_EDIT = 'delivery_zone.edit';
    case DELIVERY_ZONE_DELETE = 'delivery_zone.delete';
    case DELIVERY_ZONE_VIEW = 'delivery_zone.view';
    case FEATURED_SECTION_CREATE = 'featured_section.create';
    case FEATURED_SECTION_EDIT = 'featured_section.edit';
    case FEATURED_SECTION_DELETE = 'featured_section.delete';
    case FEATURED_SECTION_SORTING_MODIFY = 'featured_section.sorting_modify';
    case FEATURED_SECTION_VIEW = 'featured_section.view';
    case FEATURED_SECTION_SORTING_VIEW = 'featured_section.sorting_view';
    case DELIVERY_BOY_EDIT = 'delivery_boy.edit';
    case DELIVERY_BOY_DELETE = 'delivery_boy.delete';
    case DELIVERY_BOY_VIEW = 'delivery_boy.view';
    case DELIVERY_BOY_EARNING_VIEW = 'delivery_boy_earning.view';
    case DELIVERY_BOY_EARNING_PROCESS_PAYMENT = 'delivery_boy_earning.process_payment';
    case DELIVERY_BOY_CASH_COLLECTION_VIEW = 'delivery_boy_cash_collection.view';
    case DELIVERY_BOY_CASH_COLLECTION_PROCESS = 'delivery_boy_cash_collection.process';
    case DELIVERY_BOY_WITHDRAWAL_VIEW = 'delivery_boy_withdrawal.view';
    case DELIVERY_BOY_WITHDRAWAL_PROCESS = 'delivery_boy_withdrawal.process';
    case SELLER_WITHDRAWAL_VIEW = 'seller_withdrawal.view';
    case SELLER_WITHDRAWAL_PROCESS = 'seller_withdrawal.process';
    case COMMISSION_VIEW = 'commission.view';
    case COMMISSION_SETTLE = 'commission.settle';
    case ORDER_VIEW = 'orders.view';
    case RETURN_VIEW = 'return.view';
    case PRODUCT_VIEW = 'product.view';
    case PRODUCT_CREATE = 'product.create';
    case PRODUCT_EDIT = 'product.edit';
    case PRODUCT_DELETE = 'product.delete';
    case PRODUCT_STATUS_UPDATE = 'product.status_update';
    case PRODUCT_FAQS_VIEW = 'product_faqs.view';
    case PROMO_CREATE = 'promo.create';
    case PROMO_EDIT = 'promo.edit';
    case PROMO_DELETE = 'promo.delete';
    case PROMO_VIEW = 'promo.view';
    case NOTIFICATION_CREATE = 'notification.create';
    case NOTIFICATION_EDIT = 'notification.edit';
    case NOTIFICATION_DELETE = 'notification.delete';
    case NOTIFICATION_VIEW = 'notification.view';
    case STORE_VIEW = 'store.view';
    case STORE_CREATE = 'store.create';
    case STORE_EDIT = 'store.edit';
    case STORE_DELETE = 'store.delete';
    case STORE_VERIFY = 'store.verify';
    // Customers (web panel users)
    case CUSTOMER_VIEW   = 'customer.view';
    case CUSTOMER_CREATE = 'customer.create';
    case CUSTOMER_EDIT   = 'customer.edit';
    case CUSTOMER_DELETE = 'customer.delete';
    case CUSTOMER_EXPORT = 'customer.export';
    case REPORT_VIEW = 'report.view';
    case REPORT_EXPORT = 'report.export';
    case BLOG_VIEW = 'blog.view';
    case BLOG_CREATE = 'blog.create';
    case BLOG_EDIT = 'blog.edit';
    case BLOG_DELETE = 'blog.delete';
    case MENU_VIEW = 'menu.view';
    case MENU_CREATE = 'menu.create';
    case MENU_EDIT = 'menu.edit';
    case MENU_DELETE = 'menu.delete';
    case SUPPORT_TICKET_VIEW = 'support_ticket.view';
    case SUPPORT_TICKET_EDIT = 'support_ticket.edit';
    case SUPPORT_TICKET_DELETE = 'support_ticket.delete';
    case PAGE_VIEW = 'page.view';
    case PAGE_EDIT = 'page.edit';
    case ENQUIRY_VIEW = 'enquiry.view';
    case ENQUIRY_DELETE = 'enquiry.delete';
    case PRODUCT_IMPORT_VIEW = 'product_import.view';
    case PRODUCT_IMPORT_IMPORT = 'product_import.import';
    case HOME_PAGE_VIEW = 'home_page.view';
    case HOME_PAGE_EDIT = 'home_page.edit';

    public static function groupedPermissions(): array
    {
        return [
            'dashboard' => [
                'name' => 'Dashboard',
                'permissions' => [
                    self::DASHBOARD_VIEW(),
                ],
            ],
            'orders' => [
                'name' => 'Orders',
                'permissions' => [
                    self::ORDER_VIEW(),
                ],
            ],
            'report' => [
                'name' => 'Reports',
                'permissions' => [
                    self::REPORT_VIEW(),
                    self::REPORT_EXPORT(),
                ],
            ],
            'return' => [
                'name' => 'Return Orders',
                'permissions' => [
                    self::RETURN_VIEW(),
                ],
            ],
            'category' => [
                'name' => 'Category',
                'permissions' => [
                    self::CATEGORY_VIEW(),
                    self::CATEGORY_CREATE(),
                    self::CATEGORY_EDIT(),
                    self::CATEGORY_DELETE(),
                ],
            ],
            'brand' => [
                'name' => 'Brand',
                'permissions' => [
                    self::BRAND_VIEW(),
                    self::BRAND_CREATE(),
                    self::BRAND_EDIT(),
                    self::BRAND_DELETE(),
                ],
            ],
            'seller' => [
                'name' => 'Seller',
                'permissions' => [
                    self::SELLER_VIEW(),
                    self::SELLER_CREATE(),
                    self::SELLER_EDIT(),
                    self::SELLER_DELETE(),
                ],
            ],
            'store' => [
                'name' => 'Store',
                'permissions' => [
                    self::STORE_VIEW(),
                    self::STORE_CREATE(),
                    self::STORE_EDIT(),
                    self::STORE_DELETE(),
                    self::STORE_VERIFY(),
                ],
            ],
            'customers' => [
                'name' => 'Customers',
                'permissions' => [
                    self::CUSTOMER_VIEW(),
                    self::CUSTOMER_CREATE(),
                    self::CUSTOMER_EDIT(),
                    self::CUSTOMER_DELETE(),
                    self::CUSTOMER_EXPORT(),
                ],
            ],
            'products' => [
                'name' => 'Products',
                'permissions' => [
                    self::PRODUCT_VIEW(),
                    self::PRODUCT_CREATE(),
                    self::PRODUCT_EDIT(),
                    self::PRODUCT_DELETE(),
                    self::PRODUCT_STATUS_UPDATE(),
                ],
            ],
            'product_import' => [
                'name' => 'Product Import',
                'permissions' => [
                    self::PRODUCT_IMPORT_VIEW(),
                    self::PRODUCT_IMPORT_IMPORT(),
                ],
            ],
            'products_faqs' => [
                'name' => 'Products FAQs',
                'permissions' => [
                    self::PRODUCT_FAQS_VIEW(),
                ],
            ],
            'tax_class' => [
                'name' => 'Tax Rates',
                'permissions' => [
                    self::TAX_CLASS_VIEW(),
                    self::TAX_CLASS_CREATE(),
                    self::TAX_CLASS_EDIT(),
                    self::TAX_CLASS_DELETE(),
                ],
            ],
            'featured_section' => [
                'name' => 'Featured Section',
                'permissions' => [
                    self::FEATURED_SECTION_VIEW(),
                    self::FEATURED_SECTION_CREATE(),
                    self::FEATURED_SECTION_EDIT(),
                    self::FEATURED_SECTION_DELETE(),
                ],
            ],
            'featured_section_sorting' => [
                'name' => 'Featured Section Sorting',
                'permissions' => [
                    self::FEATURED_SECTION_SORTING_VIEW(),
                    self::FEATURED_SECTION_SORTING_MODIFY(),
                ],
            ],
            'promo' => [
                'name' => 'Promo',
                'permissions' => [
                    self::PROMO_VIEW(),
                    self::PROMO_CREATE(),
                    self::PROMO_EDIT(),
                    self::PROMO_DELETE(),
                ],
            ],
            'home_page' => [
                'name' => 'Home Page',
                'permissions' => [
                    self::HOME_PAGE_VIEW(),
                    self::HOME_PAGE_EDIT(),
                ],
            ],
            'blog' => [
                'name' => 'Blog',
                'permissions' => [
                    self::BLOG_VIEW(),
                    self::BLOG_CREATE(),
                    self::BLOG_EDIT(),
                    self::BLOG_DELETE(),
                ],
            ],
            'menu' => [
                'name' => 'Menus',
                'permissions' => [
                    self::MENU_VIEW(),
                    self::MENU_CREATE(),
                    self::MENU_EDIT(),
                    self::MENU_DELETE(),
                ],
            ],
            'support_ticket' => [
                'name' => 'Support Tickets',
                'permissions' => [
                    self::SUPPORT_TICKET_VIEW(),
                    self::SUPPORT_TICKET_EDIT(),
                    self::SUPPORT_TICKET_DELETE(),
                ],
            ],
            'page' => [
                'name' => 'Pages',
                'permissions' => [
                    self::PAGE_VIEW(),
                    self::PAGE_EDIT(),
                ],
            ],
            'enquiry' => [
                'name' => 'Enquiries',
                'permissions' => [
                    self::ENQUIRY_VIEW(),
                    self::ENQUIRY_DELETE(),
                ],
            ],
            'faq' => [
                'name' => 'FAQ',
                'permissions' => [
                    self::FAQ_VIEW(),
                    self::FAQ_CREATE(),
                    self::FAQ_EDIT(),
                    self::FAQ_DELETE(),
                ],
            ],
            'notification' => [
                'name' => 'Notification',
                'permissions' => [
                    self::NOTIFICATION_VIEW(),
//                    self::NOTIFICATION_CREATE(),
                    self::NOTIFICATION_EDIT(),
                    self::NOTIFICATION_DELETE(),
                ],
            ],
            'role' => [
                'name' => 'Role',
                'permissions' => [
                    self::ROLE_VIEW(),
                    self::ROLE_CREATE(),
                    self::ROLE_EDIT(),
                    self::ROLE_DELETE(),
                ],
            ],
            'permission' => [
                'name' => 'Permission',
                'permissions' => [
                    self::ROLE_PERMISSIONS_VIEW(),
                    self::ROLE_PERMISSIONS_EDIT(),
                ],
            ],
            'system_user' => [
                'name' => 'System User',
                'permissions' => [
                    self::SYSTEM_USER_VIEW(),
                    self::SYSTEM_USER_CREATE(),
                    self::SYSTEM_USER_EDIT(),
                    self::SYSTEM_USER_DELETE(),
                ],
            ],
            'setting_system' => [
                'name' => 'System Settings',
                'permissions' => [
                    self::SETTING_SYSTEM_VIEW(),
                    self::SETTING_SYSTEM_EDIT(),
                ],
            ],
            'setting_storage' => [
                'name' => 'Storage Settings',
                'permissions' => [
                    self::SETTING_STORAGE_VIEW(),
                    self::SETTING_STORAGE_EDIT(),
                ],
            ],
            'setting_email' => [
                'name' => 'Email Settings',
                'permissions' => [
                    self::SETTING_EMAIL_VIEW(),
                    self::SETTING_EMAIL_EDIT(),
                ],
            ],
            'setting_payment' => [
                'name' => 'Payment Settings',
                'permissions' => [
                    self::SETTING_PAYMENT_VIEW(),
                    self::SETTING_PAYMENT_EDIT(),
                ],
            ],
            'setting_authentication' => [
                'name' => 'Authentication Settings',
                'permissions' => [
                    self::SETTING_AUTHENTICATION_VIEW(),
                    self::SETTING_AUTHENTICATION_EDIT(),
                ],
            ],
            'setting_notification' => [
                'name' => 'Notification Settings',
                'permissions' => [
                    self::SETTING_NOTIFICATION_VIEW(),
                    self::SETTING_NOTIFICATION_EDIT(),
                ],
            ],
            'setting_web' => [
                'name' => 'Web Settings',
                'permissions' => [
                    self::SETTING_WEB_VIEW(),
                    self::SETTING_WEB_EDIT(),
                ],
            ],
        ];
    }
}
