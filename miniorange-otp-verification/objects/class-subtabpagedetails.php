<?php
/**Load Abstract Class SubtabPageDetails
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subtab page details class.
 */
if ( ! class_exists( 'SubtabPageDetails' ) ) {
	/**
	 * SubtabPageDetails class
	 */
	class SubtabPageDetails {

		/**
		 * This function returns the details of subtab page
		 *
		 * @param string $page_title title of pgae.
		 * @param string $menu_title title of menu.
		 * @param string $tab_name name of subtab.
		 * @param string $view view of subtab.
		 * @param string $id id of subtab.
		 * @param string $css css of subtab.
		 * @param string $show_in_nav whether to show the tab in navbar.
		 */
		public function __construct( $page_title, $menu_title, $tab_name, $view, $id, $css = '', $show_in_nav = true ) {
			$this->page_title  = $page_title;
			$this->menu_title  = $menu_title;
			$this->tab_name    = $tab_name;
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
		 * The menu title
		 *
		 * @var string $menu_title
		 */
		public $menu_title;


		/**
		 * Tab Name
		 *
		 * @var string $tab_name
		 */
		public $tab_name;

		/**
		 * The php page having the view
		 *
		 * @var string $view
		 */
		public $view;

		/**
		 * The ID attribute of the Tab
		 *
		 * @var string $id
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
		 * @var string $css
		 */
		public $css;
	}
}
