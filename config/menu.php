<?php

return [

    'admin' => [

        // ─── Overview ────────────────────────────────────────────────────
        'dashboard' => [
            'icon'   => 'ti-home',
            'route'  => 'admin.dashboard',
            'title'  => 'labels.dashboard',
            'active' => 'dashboard',
        ],
        

        // ─── Catalog ─────────────────────────────────────────────────────
        'products' => [
            'icon'   => 'ti-cube-spark',
            'title'  => 'labels.products',
            'active' => 'products',
            'route'  => [
                'products' => [
                    'sub_active' => 'products',
                    'sub_route'  => 'admin.products.index',
                    'sub_title'  => 'labels.products',
                    'permission' => 'product.view',
                ],
                // 'pending_approval_products' => [
                //     'sub_active'  => 'pending_approval_products',
                //     'sub_route'   => 'admin.products.index',
                //     'route_param' => ['verification_status' => 'pending_verification'],
                //     'sub_title'   => 'labels.pending_approval_products',
                //     'permission'  => 'product.view',
                // ],
                'product_faqs' => [
                    'sub_active' => 'product_faqs',
                    'sub_route'  => 'admin.product_faqs.index',
                    'sub_title'  => 'labels.product_faqs',
                    'permission' => 'product_faqs.view',
                ],
                'attributes' => [
                    'sub_active' => 'attributes',
                    'sub_route'  => 'admin.attributes.index',
                    'sub_title'  => 'labels.attributes',
                    'permission' => 'attribute.view',
                ],
            ],
        ],
        'categories' => [
            'icon'       => 'ti-category-2',
            'route'      => 'admin.categories.index',
            'title'      => 'labels.categories',
            'active'     => 'categories',
            'permission' => 'category.view',
        ],
        'brands' => [
            'icon'       => 'ti-sparkles',
            'route'      => 'admin.brands.index',
            'title'      => 'labels.brands',
            'active'     => 'brands',
            'permission' => 'brand.view',
        ],
        'inventory' => [
            'icon'   => 'ti-packages',
            'route'  => 'admin.inventory.index',
            'title'  => 'Inventory',
            'active' => 'inventory',
        ],
        // 'tax_rates' => [
        //     'icon'       => 'ti-square-rounded-percentage',
        //     'route'      => 'admin.tax-rates.index',
        //     'title'      => 'labels.tax_rates',
        //     'active'     => 'tax_rates',
        //     'permission' => 'tax_class.view',
        // ],

        // ─── Sales ───────────────────────────────────────────────────────
        'orders' => [
            'icon'       => 'ti-package',
            'route'      => 'admin.orders.index',
            'title'      => 'labels.orders',
            'active'     => 'orders',
            'permission' => 'orders.view',
        ],
        

        // ─── Customers ───────────────────────────────────────────────────
        'customers' => [
            'icon'       => 'ti-users',
            'route'      => 'admin.customers.index',
            'title'      => 'labels.customers',
            'active'     => 'customers',
            'permission' => 'customer.view',
        ],

        // ─── Store ───────────────────────────────────────────────────────
        'stores' => [
            'icon'       => 'ti-building-warehouse',
            'route'      => 'admin.sellers.store.index',
            'title'      => 'labels.stores',
            'active'     => 'stores',
            'permission' => 'store.view',
        ],

        // ─── Content & Marketing ─────────────────────────────────────────
        'promos' => [
            'icon'       => 'ti-ticket',
            'route'      => 'admin.promos.index',
            'title'      => 'labels.promos',
            'active'     => 'promos',
            'permission' => 'promo.view',
        ],
        // 'gift_cards' => [
        //     'icon'   => 'ti-gift',
        //     'title'  => 'Gift Cards',
        //     'active' => 'gift_cards',
        //     'route'  => [
        //         'all_gift_cards' => [
        //             'sub_active' => 'gift_cards',
        //             'sub_route'  => 'admin.gift-cards.index',
        //             'sub_title'  => 'All Gift Cards',
        //         ],
        //     ],
        // ],
        'home_section' => [
            'icon'   => 'ti-layout-grid',            
            'title'  => 'Home Page',
            'active' => 'home_section',
            'route'  => [
                // 'system' removed from Home Page — lives under Settings instead
                'hero_section' => [
                    'sub_active'  => 'hero_section',
                    'sub_route'   => 'admin.hero-section.show',
                    'sub_title'   => 'labels.hero_section',
                    'permission'  => 'home_page.view',
                ],
                'social_proof' => [
                    'sub_active'  => 'social_proof',
                    'sub_route'   => 'admin.social-proof.show',
                    'sub_title'   => 'Social Proof',
                    'permission'  => 'home_page.view',
                ],
                'video_story_section' => [
                    'sub_active'  => 'video_story_section',
                    'sub_route'   => 'admin.video-stories-section.show',
                    'sub_title'   => 'Video Stories',
                    'permission'  => 'home_page.view',
                ],
                'featured_products_section' => [
                    'sub_active'  => 'featured_products_section',
                    'sub_route'   => 'admin.featured-products-section.show',
                    'sub_title'   => 'Featured Products',
                    'permission'  => 'home_page.view',
                ],
                'why_choose_us' => [
                    'sub_active'  => 'why_choose_us',
                    'sub_route'   => 'admin.why-choose-us.show',
                    'sub_title'   => 'Why Choose Us',
                    'permission'  => 'home_page.view',
                ],
                'promo_banner' => [
                    'sub_active'  => 'promo_banner',
                    'sub_route'   => 'admin.promo-banner.show',
                    'sub_title'   => 'Promo Banner',
                    'permission'  => 'home_page.view',
                ],
                'newsletter_section' => [
                    'sub_active'  => 'newsletter_section',
                    'sub_route'   => 'admin.newsletter-section.show',
                    'sub_title'   => 'Newsletter Section',
                    'permission'  => 'home_page.view',
                ],
                'announcement_bar' => [
                    'sub_active'  => 'announcement_bar',
                    'sub_route'   => 'admin.announcement-bar.show',
                    'sub_title'   => 'Top Bars / Ticker',
                    'permission'  => 'home_page.view',
                ],
                'highlight_ticker' => [
                    'sub_active'  => 'highlight_ticker',
                    'sub_route'   => 'admin.highlight-ticker.show',
                    'sub_title'   => 'Highlight Ticker',
                    'permission'  => 'home_page.view',
                ],
            ]
        ],
        
        // 'featured_section' => [
        //     'icon'   => 'ti-star',
        //     'title'  => 'labels.featured_section',
        //     'active' => 'featured_section',
        //     'route'  => [
        //         'featured_section' => [
        //             'sub_active' => 'featured_section',
        //             'sub_route'  => 'admin.featured-sections.index',
        //             'sub_title'  => 'labels.featured_section',
        //             'permission' => 'featured_section.view',
        //         ],
        //         'sort_featured_section' => [
        //             'sub_active' => 'sort_featured_section',
        //             'sub_route'  => 'admin.featured-sections.sort',
        //             'sub_title'  => 'labels.sort_featured_section',
        //             'permission' => 'featured_section.sorting_view',
        //         ],
        //     ],
        // ],
        'blog' => [
            'icon'   => 'ti-article',
            'title'  => 'Blog',
            'active' => 'blog',
            'route'  => [
                'blog_settings' => [
                    'sub_active' => 'blog_settings',
                    'sub_route'  => 'admin.blog.settings.show',
                    'sub_title'  => 'Blog Settings',
                    'permission' => 'blog.view',
                ],
                'blog_categories' => [
                    'sub_active' => 'blog_categories',
                    'sub_route'  => 'admin.blog.categories.index',
                    'sub_title'  => 'Blog Categories',
                    'permission' => 'blog.view',
                ],
                'blog_posts' => [
                    'sub_active' => 'blog_posts',
                    'sub_route'  => 'admin.blog.posts.index',
                    'sub_title'  => 'Blog Posts',
                    'permission' => 'blog.view',
                ],
            ],
        ],
        'menus' => [
            'icon'   => 'ti-menu-2',
            'title'  => 'labels.menus',
            'active' => 'menus',
            'route'  => [
                'all_menus' => [
                    'sub_active' => 'menus',
                    'sub_route'  => 'admin.menus.index',
                    'sub_title'  => 'labels.all_menus',
                    'permission' => 'menu.view',
                ],
            ],
        ],
        'reports' => [
            'icon'   => 'ti-chart-bar',
            'title'  => 'Reports',
            'active' => 'reports',
            'route'  => [
                'sales_report' => [
                    'sub_active' => 'reports',
                    'sub_route'  => 'admin.reports.sales',
                    'sub_title'  => 'Sales Report',
                    'permission' => 'report.view',
                ],
                'orders_report' => [
                    'sub_active' => 'reports',
                    'sub_route'  => 'admin.reports.orders',
                    'sub_title'  => 'Orders Report',
                    'permission' => 'report.view',
                ],
                'products_report' => [
                    'sub_active' => 'reports',
                    'sub_route'  => 'admin.reports.products',
                    'sub_title'  => 'Products Report',
                    'permission' => 'report.view',
                ],
                'customers_report' => [
                    'sub_active' => 'reports',
                    'sub_route'  => 'admin.reports.customers',
                    'sub_title'  => 'Customers Report',
                    'permission' => 'report.view',
                ],
                'payment_monitor' => [
                    'sub_active' => 'payment_monitor',
                    'sub_route'  => 'admin.reports.payments',
                    'sub_title'  => 'labels.payment_monitor',
                    'permission' => 'report.view',
                ],
            ],
        ],        
        'support_tickets' => [
            'icon'   => 'ti-message-circle',
            'title'  => 'Support Tickets',
            'active' => 'support_tickets',
            'route'  => [
                'all_tickets' => [
                    'sub_active' => 'support_tickets',
                    'sub_route'  => 'admin.support-tickets.index',
                    'sub_title'  => 'All Tickets',
                    'permission' => 'support_ticket.view',
                ],
            ],
        ],
        'faqs' => [
            'icon'       => 'ti-help-circle',
            'route'      => 'admin.faqs.index',
            'title'      => 'labels.faqs',
            'active'     => 'faqs',
            'permission' => 'faq.view',
        ],
        'notifications' => [
            'icon'       => 'ti-bell-ringing',
            'route'      => 'admin.notifications.index',
            'title'      => 'labels.notifications',
            'active'     => 'notifications',
            'permission' => 'notification.view',
        ],
        'other_pages' => [
            'icon'   => 'ti-file-text',
            'title'  => 'Other Pages',
            'active' => 'other_pages',
            'route'  => [
                'cms_pages' => [
                    'sub_active' => 'cms_pages',
                    'sub_route'  => 'admin.pages.index',
                    'sub_title'  => 'Pages',
                    'permission' => 'page.view',
                ],
                'enquiries' => [
                    'sub_active' => 'enquiries',
                    'sub_route'  => 'admin.enquiries.index',
                    'sub_title'  => 'Enquiries',
                    'permission' => 'enquiry.view',
                ],
            ],
        ],

        // ─── Administration ──────────────────────────────────────────────
        'roles_permissions' => [
            'icon'   => 'ti-users-group',
            'title'  => 'labels.roles_permissions',
            'active' => 'roles_permissions',
            'route'  => [
                'roles' => [
                    'sub_active' => 'roles',
                    'sub_route'  => 'admin.roles.index',
                    'sub_title'  => 'labels.roles',
                    'permission' => 'role.view',
                ],
                'system_users' => [
                    'sub_active' => 'system_users',
                    'sub_route'  => 'admin.system-users.index',
                    'sub_title'  => 'labels.system_users',
                    'permission' => 'system_user.view',
                ],
            ],
        ],
        'settings' => [
            'icon'   => 'ti-settings',
            'title'  => 'labels.settings',
            'active' => 'settings',
            'route'  => [
                'system' => [
                    'sub_active'  => 'system',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'system'],
                    'sub_title'   => 'labels.menu_system',
                    'permission'  => 'setting.system.view',
                ],
                // 'app' => [
                //     'sub_active'  => 'app',
                //     'sub_route'   => 'admin.settings.show',
                //     'route_param' => ['setting' => 'app'],
                //     'sub_title'   => 'labels.menu_app',
                //     'permission'  => 'setting.app.view',
                // ],
                // 'home_general_settings' => [
                //     'sub_active'  => 'home_general_settings',
                //     'sub_route'   => 'admin.settings.show',
                //     'route_param' => ['setting' => 'home_general_settings'],
                //     'sub_title'   => 'labels.home_general_settings',
                //     'permission'  => 'setting.home_general_settings.view',
                // ],
                'storage' => [
                    'sub_active'  => 'storage',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'storage'],
                    'sub_title'   => 'labels.menu_storage',
                    'permission'  => 'setting.storage.view',
                ],
                'authentication' => [
                    'sub_active'  => 'authentication',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'authentication'],
                    'sub_title'   => 'labels.menu_authentication',
                    'permission'  => 'setting.authentication.view',
                ],
                'email' => [
                    'sub_active'  => 'email',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'email'],
                    'sub_title'   => 'labels.email',
                    'permission'  => 'setting.email.view',
                ],
                'payment' => [
                    'sub_active'  => 'payment',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'payment'],
                    'sub_title'   => 'labels.menu_payment',
                    'permission'  => 'setting.payment.view',
                ],
                'notification' => [
                    'sub_active'  => 'notification',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'notification'],
                    'sub_title'   => 'labels.menu_notification',
                    'permission'  => 'setting.notification.view',
                ],
                // 'delivery_boy' => [
                //     'sub_active'  => 'delivery_boy',
                //     'sub_route'   => 'admin.settings.show',
                //     'route_param' => ['setting' => 'delivery_boy'],
                //     'sub_title'   => 'labels.delivery_boy',
                //     'permission'  => 'setting.delivery_boy.view',
                // ],
                'sms' => [
                    'sub_active'  => 'sms',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'sms'],
                    'sub_title'   => 'labels.sms_settings',
                    'permission'  => 'setting.sms.view',
                ],
                'gst' => [
                    'sub_active'  => 'gst',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'gst'],
                    'sub_title'   => 'labels.gst_settings',
                    'permission'  => 'setting.gst.view',
                ],
                'seo_advanced' => [
                    'sub_active'  => 'seo_advanced',
                    'sub_route'   => 'admin.settings.show',
                    'route_param' => ['setting' => 'seo_advanced'],
                    'sub_title'   => 'labels.seo_advanced_settings',
                    'permission'  => 'setting.system.view',
                ],
                'state_shipping_rates' => [
                    'sub_active'  => 'state_shipping_rates',
                    'sub_route'   => 'admin.state-shipping-rates.index',
                    'route_param' => ['setting' => 'state_shipping_rates'],
                    'sub_title'   => 'labels.state_shipping_rates',
                    'permission' => 'setting.system.view',
                ],
                'pin_service' => [
                    'sub_active'  => 'pin_service',
                    'sub_route'   => 'admin.pin-service.index',
                    'route_param' => ['setting' => 'pin_service'],
                    'sub_title'   => 'labels.pin_service',
                    'permission' => 'setting.system.view',
                ],
            ],
        ],
        
        
        // 'system_updates' => [
        //     'icon' => 'ti-package',
        //     'route' => 'admin.system-updates.index',
        //     'title' => 'labels.system_updates',
        //     'active' => 'system_updates',
        //     'permission' => 'setting.system.view',
        // ],

        // ─── Account ─────────────────────────────────────────────────────
        'logout' => [
            'icon'  => 'ti-logout-2',
            'route' => 'admin.logout',
            'title' => 'labels.logout',
        ],
    ],

    'delivery-partner' => [
        'dashboard' => [
            'icon' => 'ti-home',
            'route' => 'delivery-partner.dashboard',
            'title' => 'labels.delivery_partner_dashboard',
        ],
    ],

    'seller' => [
        'dashboard' => [
            'icon' => 'ti-home',
            'route' => 'seller.dashboard',
            'title' => 'labels.seller_dashboard',
            'active' => 'dashboard',
        ],
        'wallet' => [
            'icon' => 'ti-wallet',
            'title' => 'labels.wallet',
            'active' => 'wallet',
            'route' => [
                'balance' => [
                    'sub_active' => 'wallet_balance',
                    'sub_route' => 'seller.wallet.index',
                    'sub_title' => 'labels.wallet_balance',
                    'permission' => 'wallet.view'

                ],
                'withdrawals' => [
                    'sub_active' => 'withdrawals',
                    'sub_route' => 'seller.withdrawals.index',
                    'sub_title' => 'labels.withdrawals',
                    'permission' => 'withdrawal.view'
                ],
                'withdrawal_history' => [
                    'sub_active' => 'withdrawal_history',
                    'sub_route' => 'seller.withdrawals.history',
                    'sub_title' => 'labels.withdrawal_history',
                    'permission' => 'withdrawal.view'
                ],
            ],
        ],
        'earnings' => [
            'icon' => 'ti-currency-dollar',
            'route' => 'seller.commissions.index',
            'title' => 'labels.earnings',
            'active' => 'earnings',
            'permission' => 'earning.view'
        ],
        'orders' => [
            'icon' => 'ti-package',
            'route' => 'seller.orders.index',
            'title' => 'labels.seller_orders',
            'active' => 'orders',
            'permission' => 'order.view'
        ],
        'return_orders' => [
            'icon' => 'ti-truck-return',
            'title' => 'labels.return_orders',
            'active' => 'return_orders',
            'route' => [
                'return_requests' => [
                    'sub_active' => 'return_requests',
                    'sub_route' => 'seller.returns.index',
                    'sub_title' => 'labels.return_requests',
                    'permission' => 'return.view'
                ],
            ],
        ],
        'categories' => [
            'icon' => 'ti-category-2',
            'route' => 'seller.categories.index',
            'title' => 'labels.seller_categories',
            'active' => 'categories',
            'permission' => 'category.view'
        ],
        'brands' => [
            'icon' => 'ti-sparkles',
            'route' => 'seller.brands.index',
            'title' => 'labels.seller_brands',
            'active' => 'brands',
            'permission' => 'brand.view'
        ],
        'attributes' => [
            'icon' => 'ti-tag-starred',
            'route' => 'seller.attributes.index',
            'title' => 'labels.attributes',
            'active' => 'attributes',
            'permission' => 'attribute.view'
        ],
        'products' => [
            'icon' => 'ti-cube-spark',
            'title' => 'labels.manage_products',
            'active' => 'products',
            'route' => [
                'products' => [
                    'sub_active' => 'products',
                    'sub_route' => 'seller.products.index',
                    'sub_title' => 'labels.seller_products',
                    'permission' => 'product.view'

                ],
                'add_products' => [
                    'sub_active' => 'add_products',
                    'sub_route' => 'seller.products.create',
                    'sub_title' => 'labels.add_products',
                    'permission' => 'product.create'

                ],
                'product_faqs' => [
                    'sub_active' => 'product_faqs',
                    'sub_route' => 'seller.product_faqs.index',
                    'sub_title' => 'labels.seller_product_faqs',
                    'permission' => 'product_faq.view'
                ],
            ],
        ],
        'tax_rates' => [
            'icon' => 'ti-square-rounded-percentage',
            'route' => 'seller.tax-rates.index',
            'title' => 'labels.seller_tax_rates',
            'active' => 'tax_rates',
            'permission' => 'tax_rate.view'
        ],
        'stores' => [
            'icon' => 'ti-building-warehouse',
            'title' => 'labels.seller_stores',
            'active' => 'stores',
            'route' => 'seller.stores.index',
            'permission' => 'store.view'
        ],
        'notifications' => [
            'icon' => 'ti-bell-ringing',
            'route' => 'seller.notifications.index',
            'title' => 'labels.seller_notifications',
            'active' => 'notifications',
            'permission' => 'notification.view'
        ],
        'roles_permissions' => [
            'icon' => 'ti-users-group',
            'title' => 'labels.seller_roles_permissions',
            'active' => 'roles_permissions',
            'route' => [
                'roles' => [
                    'sub_active' => 'roles',
                    'sub_route' => 'seller.roles.index',
                    'sub_title' => 'labels.seller_roles',
                    'permission' => 'role.view'

                ],
                'system_users' => [
                    'sub_active' => 'system_users',
                    'sub_route' => 'seller.system-users.index',
                    'sub_title' => 'labels.seller_system_users',
                    'permission' => 'system_user.view'

                ]
            ],
        ],
        'logout' => [
            'icon' => 'ti-logout-2',
            'route' => 'seller.logout',
            'title' => 'labels.seller_logout',
        ],
    ]
];
