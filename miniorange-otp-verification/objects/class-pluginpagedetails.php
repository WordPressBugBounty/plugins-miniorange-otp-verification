<?php
/**Load Interface PluginPageDetails
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PluginPageDetails' ) ) {
	/**
	 *  This class is used to generate notification settings
	 *  specific to email or sms settings. These settings are then passed
	 *  to the cURL function to send notifications.
	 */
	class PluginPageDetails {


		/**
		 * Constructor.
		 *
		 * @param string $page_title page title param.
		 * @param string $menu_slug menu slug param.
		 * @param string $menu_title meny title param.
		 * @param string $tab_name tab name param.
		 * @param string $icon tab icon.
		 * @param string $request_uri request url.
		 * @param string $view view page details.
		 * @param string $id id of page.
		 * @param string $css css of page .
		 * @param string $show_in_nav check if need to shown in navbar.
		 */
		public function __construct( $page_title, $menu_slug, $menu_title, $tab_name, $icon, $request_uri, $view, $id, $css = '', $show_in_nav = true ) {
			$this->page_title  = $page_title;
			$this->menu_slug   = $menu_slug;
			$this->menu_title  = $menu_title;
			$this->tab_name    = $tab_name;
			$this->icon        = $icon;
			$this->url         = add_query_arg( array( 'page' => $this->menu_slug ), $request_uri );
			$this->url         = remove_query_arg( array( 'addon', 'form', 'sms', 'subpage' ), $this->url );
			$this->view        = $view;
			$this->id          = $id;
			$this->show_in_nav = $show_in_nav;
			$this->css         = $css;
		}

		/**
		 * The page title
		 *
		 * @var string $page_title
		 */
		public $page_title;

		/**
		 * The menuSlug
		 *
		 * @var string $menu_slug
		 */
		public $menu_slug;


		/**
		 * The menu title
		 *
		 * @var string $menu_title
		 */
		public $menu_title;

		/**
		 * Tab Name
		 *
		 * @var String $tab_name
		 */
		public $tab_name;

		/**
		 * URL of the NavBar
		 *
		 * @var String $url
		 */
		public $url;

		/**
		 * The php page having the view
		 *
		 * @var String $view
		 */
		public $view;

		/**
		 * The php page having the icon
		 *
		 * @var String $icon
		 */
		public $icon;

		/**
		 * The ID attribute of the Tab
		 *
		 * @var String $id
		 */
		public $id;

		/**
		 * The Attribute which decides if this page should be shown
		 * in the Navbar
		 *
		 * @var bool $show_in_nav
		 */
		public $show_in_nav;

		/**
		 * The inline css to be applied to the navbar
		 *
		 * @var String
		 */
		public $css;
	}
}
