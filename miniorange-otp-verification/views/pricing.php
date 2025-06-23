<?php
/**
 * Load admin view for Licensing Tab.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoConstants;
use OTP\Helper\MoAddonListContent;
use OTP\Helper\MoUtility;
use OTP\Helper\TransactionCost;

	$checkmark = '
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
          <g id="1387d83e997b6367c4b5c211e15559b8">
            <path id="fe1f8306c6f43f39ceff3a68bab46acd" d="M7 12.2857L11.4742 15.0677C11.5426 15.1103 11.6323 15.0936 11.6809 15.0293L17 8" stroke="#00D3BA" stroke-width="2" stroke-linecap="round"></path>
          </g>
        </svg>
    ';

	$red_cross = '
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
          <g id="bb49a1baa8f2b053c609302287f4c5cb">
            <g id="2c67efdbf97e2a5d9233fce69c6c90ce">
              <path id="0a218d13db926129cd6c078df4b7e91c" d="M8 8L16 16" stroke="#FF6060" stroke-width="2" stroke-linecap="round"></path>
              <path id="659efa9552d3f2b3706cd5cc59cad8c9" d="M16 8L8 16" stroke="#FF6060" stroke-width="2" stroke-linecap="round"></path>
            </g>
          </g>
        </svg>
    ';

	$question_mark_icon = '
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <g id="5d83e4b88b8d72fdf7f1242c6e1a2758">
            <path id="1a33d648b537e4b5428ead7c276e4e43" fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM13 7C13 7.55228 12.5523 8 12 8C11.4477 8 11 7.55228 11 7C11 6.44772 11.4477 6 12 6C12.5523 6 13 6.44772 13 7ZM11 9.25C10.5858 9.25 10.25 9.58579 10.25 10C10.25 10.4142 10.5858 10.75 11 10.75H11.25V17C11.25 17.4142 11.5858 17.75 12 17.75C12.4142 17.75 12.75 17.4142 12.75 17V10C12.75 9.58579 12.4142 9.25 12 9.25H11Z" fill="#28303F"></path>
          </g>
        </svg>
    ';

	$circle_icon = '
        <svg class="min-w-[8px] min-h-[8px]" width="8" height="8" viewBox="0 0 18 18" fill="none">
            <circle id="a89fc99c6ce659f06983e2283c1865f1" cx="9" cy="9" r="7" stroke="rgb(99 102 241)" stroke-width="4"></circle>
        </svg>
    ';

	$addon_card      = 'p-5 rounded-md bg-white relative flex flex-col shadow-md';
	$addon_price_tag = 'rounded-md';
	$country_list    = MO_SMS_PRICING;

echo '
<div>
<!--  TABS CONTENT  -->
<div>
    <div class="mo-header">
        <h6 id="mo-section-heading" class="mo-heading">Licensing Page</h6>
         <a class="mo-button secondary medium flex" href="#mo_whatsapp_otp_notif_marketing" style="padding-left: 2rem;">
            <svg viewBox="0 0 58 27" id="whatsapp" height="50px" width="40px" class="flex-2" style="position: absolute; margin-right: 4rem;">
                <path fill="#25D366" d="M12 3a9 9 0 00-9 9c0 1.75.51 3.37 1.37 4.75l-1.08 3.53c-.08.27 0 .55.19.75.19.2.49.27.75.19l3.78-1.16.006-.002A8.962 8.962 0 0012 21a9 9 0 000-18zM6.01 16.5c-.41-.55-.76-1.15-1.01-1.81v-.02c-.33-.83-.5-1.74-.5-2.68C4.5 7.86 7.86 4.5 12 4.5c4.14 0 7.5 3.36 7.5 7.5 0 4.14-3.36 7.5-7.5 7.5a7.6 7.6 0 01-1.59-.19l-.015.005a7.375 7.375 0 01-2.215-.875v-.01l-1.84.56-1.21.37.36-1.16.52-1.7zm2.14-8.54a.89.89 0 01.63-.27l.01-.02c.083 0 .163.003.24.005.072.003.142.005.21.005.17.01.35.02.52.4.11.241.289.688.438 1.06.123.305.225.56.252.61.05.11.09.24.01.39l-.028.054c-.063.12-.107.202-.212.316-.04.043-.081.09-.122.137a3.417 3.417 0 01-.247.262c-.108.107-.228.227-.101.451.13.22.59.96 1.27 1.57a6.162 6.162 0 001.692 1.072c.07.032.127.057.168.078.23.12.37.1.51-.05.13-.14.59-.64.75-.86.16-.22.32-.18.53-.1.21.08 1.35.64 1.58.76l.133.068c.155.078.26.13.307.202.05.09.04.54-.16 1.06-.21.52-1.17 1.01-1.6 1.04a5.012 5.012 0 00-.124.01c-.397.038-.893.085-2.666-.62-2.2-.876-3.617-2.986-3.889-3.39a2.432 2.432 0 00-.051-.074c-.144-.195-.92-1.246-.9-2.316.019-1.035.56-1.565.807-1.808l.043-.042z"></path>
           </svg>
            <span>' . esc_html( mo_( 'WhatsApp' ) ) . '</span>
        </a>
        <a class="mo-button secondary medium flex" href="#mo_registration_firebase_layout" style="padding-left: 2rem;">
            <svg viewBox="0 0 58 27" id="firebase" height="50px" width="40px" class="flex-2" style="position: absolute; margin-right: 9rem;">
                <path fill="#FFA000" d="m14.714 8.669-2.4 2.235-2.228-4.496 1.151-2.585c.291-.516.767-.522 1.058 0l2.419 4.846z"></path>
                <path fill="#F57F17" d="m12.314 10.903-8.979 8.351 6.751-12.846 2.228 4.495z"></path>
                <path fill="#FFCA28" d="M17.346 5.251c.43-.41.873-.271.985.31l2.334 13.58-7.742 4.648c-.272.152-.992.211-.992.211s-.655-.08-.906-.218l-7.689-4.528 14.01-14.003z"></path>
                <path fill="#FFA000" d="m10.086 6.408-6.75 12.846L6.344.477c.113-.582.443-.641.74-.126l3.002 6.057z"></path>
            </svg>
            <span>' . esc_html( mo_( 'Firebase Gateway Plan' ) ) . '</span>
        </a>
        <a class="mo-button secondary medium" href="#otp_pay_method">' . esc_html( mo_( 'Payments Methods' ) ) . '</a>
    </div>
    <div class="text-center pt-mo-3 pl-mo-6" >
        <p>The plans depend on your chosen SMS Gateway. Discover more about <a href="https://faq.miniorange.com/knowledgebase/use-own-gateway-plugin/" target="_blank"><b><u>SMS Gateway</u></b></a></p>
    </div>
    <div class="bg-slate-50">
        <!--  TABS  -->
        <div id="mo_select_gateway_type_div" class="mo-tab-container">
            <div class="mo-tabs-wrapper">
                <a id="pricingtabitem" class="mo-tab-item active">Use your own Gateway</a>
                <a id="mogatewaytabitem"  class="mo-tab-item">Use miniOrange Gateway</a>
            </div>           
        </div>
    </div>
    <div id="mo-new-pricing-page" class="mo-new-pricing-page bg-white rounded-md">
                <!--  PRICING SECTION  -->
                <section id="mo_otp_plans_pricing_table">
                    <div id="pricing_plans_div" class="mo-pricing-snippet-grid">

                        <div class="mo-pricing-card" >
                            <div>
                                <h5>Custom Gateway<br>Plan</h5>
                                <div class="my-mo-4 flex gap-mo-4">
                                    <div class="flex">
                                        <h1 class="m-mo-0">$29</h1><span style="font-size:1rem; margin-top:5%"><i>/Year</i></span>
                                    </div>
                                </div>
                            </div> 

                            <ul class="mt-mo-4 grow" >';
foreach ( $custom_gateway_plan_features as $features ) {
	echo '   <li class="feature-snippet">
                                <span class="mt-mo-2.5">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
                                <p class="m-mo-0">' . wp_kses( $features, MoUtility::mo_allow_html_array() ) . '</p>
                            </li>';
}
echo '                           </ul>

                            <button class="w-full mo-button primary" onclick="mo_otp_upgradeform_submit(\'wp_email_verification_intranet_basic_plan\')">Upgrade Now</button>
                        </div>

                        <div class="mo-pricing-card">
                            <div>
                                <h5>Twilio Gateway + MSG91 <br>Plan</h5>
                                <div class="my-mo-4 flex gap-mo-4">
                                    <div class="flex">
                                        <h1 class="m-mo-0">$49</h1><span style="font-size:1rem; margin-top:5%"><i>/Year</i></span>
                                    </div>
                                </div>
                            </div>    
                            
                            <ul class="mt-mo-4 grow" >';
foreach ( $twilio_gateway_plan_features as $features ) {
	echo '   <li class="feature-snippet">
                                <span class="mt-mo-2.5">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
                                <p class="m-mo-0">' . wp_kses( $features, MoUtility::mo_allow_html_array() ) . '</p>
                            </li>';
}
echo '                                  
                            </ul>

                            <button class="w-full mo-button primary" onclick="mo_otp_upgradeform_submit(\'wp_email_verification_intranet_twilio_basic_plan\')">Upgrade Now</button>

                        </div>

                        <div class="mo-pricing-card premium">
                            <div>
                                <h5>Enterprise All Inclusive<br>& AWS SNS</h5>
                                <div class="my-mo-4 flex gap-mo-4">
                                    <div class="flex">
                                        <h1 class="m-mo-0 text-white">$99</h1><span style="font-size:1rem; margin-top:5%"><i>/Year</i></span>
                                    </div>
                                </div>
                            </div>    
                            
                            <ul class="mt-mo-4 grow" >';
foreach ( $enterprise_plan_features as $features ) {
	echo '   <li class="feature-snippet">
                                <span class="mt-mo-2.5">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
                                <p class="m-mo-0">' . wp_kses( $features, MoUtility::mo_allow_html_array() ) . '</p>
                            </li>';
}
echo '                          
                            </ul>
                            <button class="w-full mo-button primary" onclick="mo_otp_upgradeform_submit(\'wp_email_verification_intranet_enterprise_plan\')">Upgrade Now</button>
                        </div>


                        <div class="mo-pricing-card" style="border:none;">
                            <div>
                                <h5>Woocommerce OTP & Notification Plan</h5>
                                <div class="my-mo-4 flex gap-mo-4">
                                    <div class="flex">
                                        <h1 class="m-mo-0">$149</h1><span style="font-size:1rem; margin-top:5%"><i>/Year</i></span>
                                    </div>
                                </div>
                            </div>    
                            
                            <ul class="mt-mo-4 grow">';
foreach ( $woocommerce_plan_features as $features ) {
	if ( 'OTP & Notifications Via WhatsApp' === $features ) {
		echo ' <li class="feature-snippet">
                                    <span class="mt-mo-2.5" style="margin-left:-6px;margin-top:-10px;margin-right:8px;margin-bottom:5px;">
                                        <svg viewBox="0 0 58 27" id="whatsapp" height="50px" width="50px" class="flex-2" style="position: absolute; margin-right: 4rem;">
                                                <path fill="#25D366" d="M12 3a9 9 0 00-9 9c0 1.75.51 3.37 1.37 4.75l-1.08 3.53c-.08.27 0 .55.19.75.19.2.49.27.75.19l3.78-1.16.006-.002A8.962 8.962 0 0012 21a9 9 0 000-18zM6.01 16.5c-.41-.55-.76-1.15-1.01-1.81v-.02c-.33-.83-.5-1.74-.5-2.68C4.5 7.86 7.86 4.5 12 4.5c4.14 0 7.5 3.36 7.5 7.5 0 4.14-3.36 7.5-7.5 7.5a7.6 7.6 0 01-1.59-.19l-.015.005a7.375 7.375 0 01-2.215-.875v-.01l-1.84.56-1.21.37.36-1.16.52-1.7zm2.14-8.54a.89.89 0 01.63-.27l.01-.02c.083 0 .163.003.24.005.072.003.142.005.21.005.17.01.35.02.52.4.11.241.289.688.438 1.06.123.305.225.56.252.61.05.11.09.24.01.39l-.028.054c-.063.12-.107.202-.212.316-.04.043-.081.09-.122.137a3.417 3.417 0 01-.247.262c-.108.107-.228.227-.101.451.13.22.59.96 1.27 1.57a6.162 6.162 0 001.692 1.072c.07.032.127.057.168.078.23.12.37.1.51-.05.13-.14.59-.64.75-.86.16-.22.32-.18.53-.1.21.08 1.35.64 1.58.76l.133.068c.155.078.26.13.307.202.05.09.04.54-.16 1.06-.21.52-1.17 1.01-1.6 1.04a5.012 5.012 0 00-.124.01c-.397.038-.893.085-2.666-.62-2.2-.876-3.617-2.986-3.889-3.39a2.432 2.432 0 00-.051-.074c-.144-.195-.92-1.246-.9-2.316.019-1.035.56-1.565.807-1.808l.043-.042z"></path>
                                        </svg>
                                    </span>    
                                    <p class="m-mo-0">' . esc_html( mo_( 'OTP & Notifications Via WhatsApp.' ) ) . '</p>
                                </li>';

	} else {
		echo '   <li class="feature-snippet">
        <span class="mt-mo-2.5">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
        <p class="m-mo-0">' . wp_kses( $features, MoUtility::mo_allow_html_array() ) . '</p>
    </li>';
	}
}

echo '                           
                            </ul>
                            <a class="w-full mo-button primary" href="https://wordpress.org/plugins/miniorange-sms-order-notification-otp-verification/" target="_blank">Try The Free Plan Now!</a><br>
                            <button class="w-full mo-button primary" onclick="mo_otp_upgradeform_submit(\'wp_email_verification_intranet_woocommerce_plan\')">Upgrade Now</button>
                        </div>

                    </div>

                <!--  DETAILED PLAN  -->

                <div class="overflow-x-auto relative rounded-b-lg">
                    <table id="pricing-table" class="mo-table mt-mo-4">
                        <tbody>';

							echo '<tr>';
echo '<th scope="row" class="py-mo-4 pr-mo-6 text-md mo_feature_pricing_row">
        ' . esc_html( mo_( 'Forms Supported' ) ) . '<br>
        <i><a class="mo_links mo_pricing_links" href="' . esc_url( 'https://plugins.miniorange.com/otp-verification-forms' ) . '" target="_blank">' . esc_html( mo_( 'Click here to check all supported forms' ) ) . '</a></i>
      </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Custom Gateway with Addons' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Twilio Gateway with Addons + MSG91' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Enterprise All Inclusive + AWS SNS' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Woocommerce OTP & Notification Plan' ) ) . '
                                </th>';
echo '</tr>';

foreach ( $premium_forms_custom_gateway as $form => $data ) {
	echo '<tr class="bg-white border-b">';
	echo '<th scope="row" class="mo-caption-pricing py-mo-2 px-mo-6">' . esc_html( $form ) . '</th>';

	foreach ( $data['feature'] as $feature ) {
		echo '<td class="py-mo-2 pl-mo-24">';
		echo wp_kses( 'checkmark' === $feature ? $checkmark : $red_cross, MoUtility::mo_allow_svg_array() );
		echo '</td>';
	}
	echo '</tr>';
}

echo '<tr>';
echo '<th scope="row" class="py-mo-4 pr-mo-6 text-md mo_feature_pricing_row">
        Gateways Supported<br>
        <i><a class="mo_links mo_pricing_links" href="' . esc_url( $gateways_supported['Gateways Supported']['link'] ) . '" target="_blank">' . esc_html( mo_( 'Click here to check all supported gateways' ) ) . '</a></i>
      </th>
        <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Custom Gateway with Addons' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                   ' . esc_html( mo_( 'Twilio Gateway with Addons + MSG91' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Enterprise All Inclusive + AWS SNS' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                   ' . esc_html( mo_( ' Woocommerce OTP & Notification Plan' ) ) . '
                                </th>';
echo '</tr>';

foreach ( $gateways_supported as $gateway => $data ) {
	echo '<tr class="bg-white border-b">';
	echo '<th scope="row" class="mo-caption-pricing py-mo-2 px-mo-6">' . esc_html( $gateway ) . '</th>';

	foreach ( $data['feature'] as $feature ) {
		echo '<td class="py-mo-2 pl-mo-24">';
		echo wp_kses( 'checkmark' === $feature ? $checkmark : $red_cross, MoUtility::mo_allow_svg_array() );
		echo '</td>';
	}
	echo '</tr>';
}

echo '<tr>';
echo '<th scope="row" class="py-mo-4 px-mo-6 text-md mo_feature_pricing_row">Features</th>
  <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Custom Gateway with Addons' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Twilio Gateway with Addons + MSG91' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Enterprise All Inclusive + AWS SNS' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Woocommerce OTP & Notification Plan' ) ) . '
                                </th>';

echo '</tr>';

foreach ( $premium_features as $feature => $data ) {
	echo '<tr class="bg-white border-b">';
	echo '<th scope="row" class="mo-caption-pricing py-mo-2 px-mo-6">' . esc_html( $feature ) . '</th>';

	foreach ( $data['feature'] as $feature ) {
		echo '<td class="py-mo-2 pl-mo-24">';
		echo wp_kses( 'checkmark' === $feature ? $checkmark : $red_cross, MoUtility::mo_allow_svg_array() );
		echo '</td>';
	}
	echo '</tr>';
}

echo '

                        </tbody>
                    </table>
                </div>        
            </section>
            
            <section id="mo_otp_miniorange_gateway_pricing" style="display: none;">
                <div class="bg-slate-50">

                    <div class="mo-pricing-snippet-grid">

                        <div class="mo-miniorange-pricing-card" >
                            <div>
                                <h5>MiniOrange Gateway Plan</h5>
                                <div class="my-mo-4 flex gap-mo-4">
                                    <h6 class="m-mo-0 font-medium">(Transaction-Based Pricing)</h6>
                                </div>
                            </div> 

                            <ul class="mt-mo-4 grow" >';

foreach ( $miniorange_gateway_plan_features as $features ) {
					echo '   <li class="feature-snippet">
                          <span class="mt-mo-2.5">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
                                <p class="m-mo-0">' . wp_kses( $features, MoUtility::mo_allow_html_array() ) . '</p>
                            </li>';
}
echo '  
                                
                               <li class="feature-snippet" style="display: table;margin: 0 auto;width: 100%;">
                                    <br>
                                    <br>
									<form action="" method="post" id="mo_sms_pricing" >';
									wp_nonce_field( 'mosmsnonce', 'mo_sms_pricing_nonce' );
echo '									<select name="languages" style="margin-bottom:0.5rem;width:100%;" id="mochoosecountry">
											<option>Select your target country</option>';
foreach ( $country_list  as $key => $value ) {
	echo '									<option value="' . esc_attr( $key ) . '">' . esc_attr( $key ) . '</option>';
}
echo '									</select>
										<select name="transactions" id="mosmspricing" style="width:100%;">
											<option id="moloading">' . esc_html( mo_( 'Check SMS Transaction Pricing*' ) ) . '</option>
											<option id="moloading">' . esc_html( mo_( 'Select the target country to check pricing' ) ) . '</option>
										</select>
                                        <select class="mt-mo-2 w-full" name="email_transactions" id="moemailpricing" >
											<option >' . esc_html( mo_( 'Check Email Transaction Pricing' ) ) . '</option>
											<option >100 transactions- $2</option>
                                            <option >500 transactions- $5</option>
                                            <option >1000 transactions- $7</option>
                                            <option >5000 transactions- $20</option>
                                            <option >10000 transactions- $30</option>
                                            <option >50000 transactions- $45</option>
										</select>
									</form>
								</li>
                            </ul>

                            <button class="w-full mo-button primary" onclick="mo_otp_upgradeform_submit(\'wp_otp_verification_basic_plan\')">Upgrade Now</button>
                        </div>

                        <div class="mo-miniorange-pricing-card premium">
                            <div>
                                <h5>Enterprise All Inclusive<br>& AWS SNS</h5>
                                <div class="my-mo-4 flex gap-mo-4">
                                    <div class="flex">
                                        <h1 class="m-mo-0 text-white">$99</h1><span style="font-size:1rem; margin-top:5%"><i>/Year</i></span>
                                    </div>
                                </div>
                            </div>    
                            
                            <ul class="mt-mo-4 grow" >';
foreach ( $enterprise_plan_features as $features ) {
	echo '   <li class="feature-snippet">
                                <span class="mt-mo-2.5">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
                                <p class="m-mo-0">' . wp_kses( $features, MoUtility::mo_allow_html_array() ) . '</p>
                            </li>';
}
echo '      
                            </ul>
                            <button class="w-full mo-button primary" onclick="mo_otp_upgradeform_submit(\'wp_email_verification_intranet_enterprise_plan\')">Upgrade Now</button>
                        </div>


                        <div class="mo-miniorange-pricing-card" style="border:none;">
                            <div>
                                <h5>Woocommerce OTP & Notification Plan</h5>
                                <div class="my-mo-4 flex gap-mo-4">
                                    <div class="flex">
                                        <h1 class="m-mo-0">$149</h1><span style="font-size:1rem; margin-top:5%"><i>/Year</i></span>
                                    </div>
                                </div>
                            </div>    
                            
                             <ul class="mt-mo-4 grow">';
foreach ( $woocommerce_plan_features as $features ) {
	if ( 'OTP & Notifications Via WhatsApp' === $features ) {
		echo ' <li class="feature-snippet">
                                     <span class="mt-mo-2.5" style="margin-left:-6px;margin-top:-10px;margin-right:8px;margin-bottom:5px;">
                                         <svg viewBox="0 0 58 27" id="whatsapp" height="50px" width="50px" class="flex-2" style="position: absolute; margin-right: 4rem;">
                                                 <path fill="#25D366" d="M12 3a9 9 0 00-9 9c0 1.75.51 3.37 1.37 4.75l-1.08 3.53c-.08.27 0 .55.19.75.19.2.49.27.75.19l3.78-1.16.006-.002A8.962 8.962 0 0012 21a9 9 0 000-18zM6.01 16.5c-.41-.55-.76-1.15-1.01-1.81v-.02c-.33-.83-.5-1.74-.5-2.68C4.5 7.86 7.86 4.5 12 4.5c4.14 0 7.5 3.36 7.5 7.5 0 4.14-3.36 7.5-7.5 7.5a7.6 7.6 0 01-1.59-.19l-.015.005a7.375 7.375 0 01-2.215-.875v-.01l-1.84.56-1.21.37.36-1.16.52-1.7zm2.14-8.54a.89.89 0 01.63-.27l.01-.02c.083 0 .163.003.24.005.072.003.142.005.21.005.17.01.35.02.52.4.11.241.289.688.438 1.06.123.305.225.56.252.61.05.11.09.24.01.39l-.028.054c-.063.12-.107.202-.212.316-.04.043-.081.09-.122.137a3.417 3.417 0 01-.247.262c-.108.107-.228.227-.101.451.13.22.59.96 1.27 1.57a6.162 6.162 0 001.692 1.072c.07.032.127.057.168.078.23.12.37.1.51-.05.13-.14.59-.64.75-.86.16-.22.32-.18.53-.1.21.08 1.35.64 1.58.76l.133.068c.155.078.26.13.307.202.05.09.04.54-.16 1.06-.21.52-1.17 1.01-1.6 1.04a5.012 5.012 0 00-.124.01c-.397.038-.893.085-2.666-.62-2.2-.876-3.617-2.986-3.889-3.39a2.432 2.432 0 00-.051-.074c-.144-.195-.92-1.246-.9-2.316.019-1.035.56-1.565.807-1.808l.043-.042z"></path>
                                         </svg>
                                     </span>    
                                     <p class="m-mo-0">' . esc_html( mo_( 'OTP & Notifications Via WhatsApp.' ) ) . '</p>
                                 </li>';

	} else {
		echo '   <li class="feature-snippet">
         <span class="mt-mo-2.5">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
         <p class="m-mo-0">' . wp_kses( $features, MoUtility::mo_allow_html_array() ) . '</p>
     </li>';
	}
}
echo '     </ul>
                            <a class="w-full mo-button primary" href="https://wordpress.org/plugins/miniorange-sms-order-notification-otp-verification/" target="_blank">Try The Free Plan Now!</a><br>
                            <button class="w-full mo-button primary" onclick="mo_otp_upgradeform_submit(\'wp_email_verification_intranet_woocommerce_plan\')">Upgrade Now</button>
                        </div>

                    </div>
                </div>

                <!--  DETAILED PLAN  -->

                <div class="overflow-x-auto relative rounded-b-lg">
                    <table id="pricing-table" class="mo-table mt-mo-4" >
                        <tbody>';

										echo '<tr>';
echo '<th scope="row" class="py-mo-4 pr-mo-6 text-md mo_feature_pricing_row">
           ' . esc_html( mo_( 'Forms Supported' ) ) . '<br>
            <i><a class="mo_links mo_pricing_links" href="' . esc_url( 'https://plugins.miniorange.com/otp-verification-forms' ) . '" target="_blank">' . esc_html( mo_( 'Click here to check all supported forms' ) ) . '</a></i>
        </th>
        <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Miniorange Gateway Plan' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Enterprise All Inclusive + AWS SNS' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Woocommerce OTP & Notification Plan' ) ) . '
                                </th>';
echo '</tr>';

foreach ( $premium_forms_mo_gateway as $form => $data ) {
	echo '<tr class="bg-white border-b">';
	echo '<th scope="row" class="mo-caption-pricing py-mo-2 px-mo-6">' . esc_html( $form ) . '</th>';

	foreach ( $data['feature'] as $feature ) {
		echo '<td class="py-mo-2 pl-mo-24">';
		echo wp_kses( 'checkmark' === $feature ? $checkmark : $red_cross, MoUtility::mo_allow_svg_array() );
		echo '</td>';
	}
	echo '</tr>';
}echo '<tr>';
echo '<th scope="row" class="py-mo-4 pr-mo-6 text-md mo_feature_pricing_row">
        Gateways Supported<br>
        <i><a class="mo_links mo_pricing_links" href="' . esc_url( $gateways_supported['Gateways Supported']['link'] ) . '" target="_blank">' . esc_html( mo_( 'Click here to check all supported gateways' ) ) . '</a></i>
      </th>
      <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Miniorange Gateway Plan' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Enterprise All Inclusive + AWS SNS' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Woocommerce OTP & Notification Plan' ) ) . '
                                </th>';
echo '</tr>';

foreach ( $gateways_supported_mo as $gateway => $data ) {
	echo '<tr class="bg-white border-b">';
	echo '<th scope="row" class="mo-caption-pricing py-mo-2 px-mo-6">' . esc_html( $gateway ) . '</th>';

	foreach ( $data['feature'] as $feature ) {
		echo '<td class="py-mo-2 pl-mo-24">';
		echo wp_kses( 'checkmark' === $feature ? $checkmark : $red_cross, MoUtility::mo_allow_svg_array() );
		echo '</td>';
	}
	echo '</tr>';
}
echo '<tr>';
echo '<th scope="row" class="py-mo-4 px-mo-6 text-md mo_feature_pricing_row">Features</th>
  <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Miniorange Gateway Plan' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Enterprise All Inclusive + AWS SNS' ) ) . '
                                </th>
                                <th scope="col" class="mo-table-block">
                                    ' . esc_html( mo_( 'Woocommerce OTP & Notification Plan' ) ) . '
                                </th>';

echo '</tr>';
foreach ( $premium_features_mo as $feature => $data ) {
	echo '<tr class="bg-white border-b">';
	echo '<th scope="row" class="mo-caption-pricing py-mo-2 px-mo-6">' . esc_html( $feature ) . '</th>';

	foreach ( $data['feature'] as $feature ) {
		echo '<td class="py-mo-2 pl-mo-24">';
		echo wp_kses( 'checkmark' === $feature ? $checkmark : $red_cross, MoUtility::mo_allow_svg_array() );
		echo '</td>';
	}
	echo '</tr>';
}
echo '
                        </tbody>
                    </table>
                    <div class="mo_otp_note px-mo-8 pb-mo-4">              
                        <p class="m-mo-0" style="font-size:12px;">*The pricing for SMS transactions is subject to change in accordance with updates from network providers.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
';

echo '
    <script>

        const {origin,pathname} = window.location;
        const moSectionHeading = document.getElementById("mo-section-heading");
       
        function toggleClasses(node,add,remove) {
            add.forEach(className => {
                node.classList.add(className)
            })
            remove.forEach(className => {
                node.classList.remove(className)
            })
        }

        const urlParams = new URLSearchParams(location.search);
        let params = {};
        for (const [key, value] of urlParams) {
            params[key] = value
        }
        
        const pricingPage = document.getElementById("mo_otp_plans_pricing_table");
        const addonsPage = document.getElementById("mo_otp_miniorange_gateway_pricing");


        const pricingTabItem = document.getElementById("pricingtabitem");
        const mogatewaytabitem = document.getElementById("mogatewaytabitem");
        
        mogatewaytabitem.addEventListener("click",function(){
            addonsPage.style.display = "block";
            pricingPage.style.display = "none";
            
            mogatewaytabitem.classList.add("active")
            pricingTabItem.classList.remove("active")
        })
        pricingTabItem.addEventListener("click",function(){
            addonsPage.style.display = "none";
            pricingPage.style.display = "block";

            pricingTabItem.classList.add("active")
            mogatewaytabitem.classList.remove("active")
        })
        
    </script>
';
echo '<div class="m-mo-4 border dark:border-gray-700" id="mo_whatsapp_otp_notif_marketing" >
                <div class="mo-header">
                    <div class="flex flex-1 gap-mo-4">
                        <img src="' . esc_url( MOV_WHATSAPP ) . '" style="height:40px;width:40px;" >
                        <p class="mo-heading flex-1 mt-mo-2">' . esc_html( mo_( 'OTP & Notifications Via WhatsApp' ) ) . '</p>
                    </div>
                    <a class="mo-button inverted flex-2"  onclick="otpSupportOnClick(\'Hi! I am interested in using WhatsApp for my website, can you please schedule a demo?\');">Get Demo</a><br>
                </div>
                <div class="pt-mo-8">
                    <div class="px-mo-8 py-mo-3">' . esc_html( mo_( 'We provide OTP & Notifications via WhatsApp in our plugin. Use your own business account or miniOrange business account for sending the WhatsApp OTPs & Notifications.' ) ) . '
                    </div>
                    <div class="flex ml" style="margin-left:3%;">
                            <div class="flex-1 p-mo-8">';
foreach ( $whatsapp_plugin_features1 as $feature ) {
	echo '  <li class="feature-snippet">
                                        <span class="mt-mo-1.5">';
	echo wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() );
	echo '  </span><p class="m-mo-0">';
	echo wp_kses( $feature, MoUtility::mo_allow_html_array() );
	echo '  </p>
                                    </li>';
}
															echo '
                                                        </div>
                                                        <div class="flex-1 p-mo-8">';
foreach ( $whatsapp_plugin_features2 as $feature ) {
	echo '  <li class="feature-snippet">
                                        <span class="mt-mo-1.5">';
	echo wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() );
	echo '  </span><p class="m-mo-0">';
	echo esc_html( $feature );
	echo '  </p>
                                    </li>';
}
							echo '
                            </div>
                    </div>
                </div>
            </div>';
echo '
</div>
            <div class="m-mo-4 border dark:border-gray-700" id="mo_registration_firebase_layout" >
                <div class="mo-header">
                    <div class="flex flex-1 gap-mo-4">
                        <img src="' . esc_url( MOV_FIREBASE ) . '" style="height:40px;width:40px;" >
                        <p class="mo-heading flex-1 mt-mo-2">' . esc_html( mo_( 'Firebase Gateway Plan' ) ) . '</p>
                    </div>
                    <a href="https://wordpress.org/plugins/miniorange-firebase-sms-otp-verification/" class="mo-button inverted flex-2" target="_blank" id="mo_firebase_plan_download">Get this Plugin</a>
                </div>
                <div class="pt-mo-8">
                    <div class="px-mo-8 py-mo-3">' . esc_html( mo_( ' We have a seperate plugin for the OTP Verification using the Firebase Gateway. Use Firebase as your custom SMS gateway to send One Time Passcodes (OTP).' ) ) . '
                    </div>
                    <div class="mo_firebase_feature_container" style="display:flex;border-radius: 7px;margin-left: 3%;line-height: 175%;">
                            <div class="flex-1 p-mo-8">';
foreach ( $firebase_plugin_features1 as $feature ) {
	echo '  <li class="feature-snippet">
            <span class="mt-mo-1.5">';
	echo wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() );
	echo '  </span><p class="m-mo-0">';
	echo esc_html( $feature );
	echo '  </p>
        </li>';
}
								echo '
                            </div>
                            <div class="flex-1 p-mo-8">';
foreach ( $firebase_plugin_features2 as $feature ) {
	echo '  <li class="feature-snippet">
            <span class="mt-mo-1.5">';
	echo wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() );
	echo '  </span><p class="m-mo-0">';
	echo esc_html( $feature );
	echo '  </p>
        </li>';
}
								echo '
                            </div>
                    </div>
                </div>
            </div>  ';

echo '
     <div class="m-mo-4 border dark:border-gray-700" id="otp_payment">
        <div id="otp_pay_method">
            <div class="mo-header">
                <p class="mo-heading flex-1 mt-mo-2">' . esc_html( mo_( 'Supported Payment Methods' ) ) . '</p>              
            </div>
            <div class="mo-pricing-container">
                <div class="mo-card-pricing-deck">
                    <div class="mo-card-pricing mo-animation">
                        <div class="mo-card-pricing-header">
                            <img  src="' . esc_url( MOV_CARD ) . '"  style="size: landscape;width: 100px; height: 27px; margin-bottom: 4px;margin-top: 4px;opacity: 1;padding-left: 8px;">
                        </div>
                        <hr style="border-top: 4px solid #fff;">
                        <div class="mo-card-pricing-body">
                            <p>If payment is made through Intenational Credit Card/Debit card, the license will be created automatically once payment is completed.</p>
                            <p style="margin-top: 20%;"><i><b><a class="mo_links" href=' . esc_url( MoConstants::FAQ_PAY_URL ) . ' target="blank">Click Here</a> to know more.</b></i></p>
                        </div>
                    </div>
                    <div class="mo-card-pricing mo-animation">
                        <div class="mo-card-pricing-header">
                            <img  src="' . esc_url( MOV_NETBANK ) . '"  style="size: landscape;width: 100px; height: 27px; margin-bottom: 4px;margin-top: 4px;opacity: 1;padding-left: 8px;">
                        </div>
                        <hr style="border-top: 4px solid #fff;">
                        <div class="mo-card-pricing-body">
                            <p>If you want to use net banking for payment then contact us at <i><b style="color:#1261d8">' . esc_html( MoConstants::SUPPORT_EMAIL ) . '</b></i> so that we can provide you bank details. </i></p>
                            <p style="margin-top: 32%;"><i><b>Note:</b> There is an additional 18% GST applicable via Bank Transfer.</i></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mo_otp_note px-mo-8 pb-mo-4 mx-mo-16 my-mo-4">
                <p><b>Note :</b> Once you have paid through Net Banking, please inform us so that we can confirm and update your License.</p>
                <p>For more information about payment methods visit <i><u><a href=' . esc_url( MoConstants::FAQ_PAY_URL ) . ' target="_blank">Supported Payment Methods.</a></u></i></p></p>
            </div>
        </div>
    </div>
    <div class="m-mo-4 border dark:border-gray-700" >
        <div class="mo-header">
            <p class="mo-heading flex-1 mt-mo-2">' . esc_html( mo_( 'Refund and Privacy Policy' ) ) . '</p>        
        </div>
        <div class="mo_otp_note px-mo-8 pb-mo-4 mx-mo-16 my-mo-4">
            <p><b>Note :</b> Please read the <i><u><a class="font-semibold" href="https://plugins.miniorange.com/end-user-license-agreement" target="_blank">Refund Policy</a></u></i>  and <i><u><a class="font-semibold" href="https://plugins.miniorange.com/wp-content/uploads/2023/08/Plugins-Privacy-Policy.pdf" target="_blank">Plugin Privacy Policy</a></u></i> before upgrading to any plan.</p>
        </div>
    </div>
    
    <form style="display:none;" id="mo_upgrade_form" action="' . esc_url( $portal_host ) . '" target="_blank" method="post">
        <input type="text" name="requestOrigin" id="requestOriginUpgrade"  />
        <input type="text" name="payment_referer" value="' . esc_url( admin_url() ) . 'admin.php?page=mootppricing">
    </form>

    <form id="mo_ln_form" style="display:none;" action="" method="post">';

		wp_nonce_field( $nonce );

	echo '<input type="hidden" name="option" value="check_mo_ln" />
    </form>
    <script>
    $mo = jQuery;
    $mo(document).ready(function () {
        var subPage = window.location.href.split("subpage=")[1];
            if(subPage !== "undefined")
            {
                if(subPage=="mogateway"){
                   mo_otp_show_mo_gateway()
                }
            }
        })
        function mo_otp_upgradeform_submit(planType){
            jQuery("input[name=\'requestOrigin\']").val(planType);
            jQuery("#mo_upgrade_form").submit();
        }
        function mo_otp_show_plans(){
            $mo("#mo_otp_plans_pricing_table").show();
            $mo("#mo_otp_miniorange_gateway_pricing").hide();
            $mo("#mo_otp_show_monthly_plan").hide();
        }
        function mo_otp_show_mo_gateway(){
            $mo("#premium_addons").prop("checked",true);
            $mo("#mo_otp_miniorange_gateway_pricing").show();
            $mo("#mo_otp_plans_pricing_table").hide();
            $mo("#mo_otp_show_monthly_plan").hide();
        }
        function mo_otp_show_monthly_plan(){
            $mo("#monthly_plan").prop("checked",true);
            $mo("#mo_otp_miniorange_gateway_pricing").hide();
            $mo("#mo_otp_plans_pricing_table").hide();
            $mo("#mo_otp_show_monthly_plan").show();
        }
        function mo_get_montly_plan_data()
        {
            var monthly_sms = $mo("#mo_monthly_sms").val();
            var monthly_email = $mo("#mo_monthly_email").val();
            var monthly_country = $mo("#mo_country_code option:selected" ).text();
            var queryBody = "Hi! I am interested in the miniOrange monthly subscription module, My target country is monthly_country, Please provide a quote for monthly_sms SMS and monthly_email Emails per month.";
            var mapObj = {
               monthly_country:monthly_country,
               monthly_sms:monthly_sms,
               monthly_email:monthly_email
            };
            var queryReplaced = queryBody.replace(/monthly_country|monthly_sms|monthly_email/gi, function(matched){
              return mapObj[matched];
            });
            otpSupportOnClick(queryReplaced);
        }
    </script>';
