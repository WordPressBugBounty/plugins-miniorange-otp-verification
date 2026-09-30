<?php
/**
 * Admin view for the Export Configuration tab.
 *
 * @var string $ec_nonce Nonce action string for the export form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="moExportConfiguration" class="p-mo-4">

	<div class="mo-header">
		<p class="mo-heading flex-1"><?php echo esc_html__( 'Export Configuration', 'miniorange-otp-verification' ); ?></p>
	</div>

	<div class="border rounded-mo-smooth bg-mo-primary-bg my-mo-4 p-mo-6">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( $ec_nonce ); ?>
			<input type="hidden" name="action" value="mo_customer_validation_export_settings" />

			<h3 class="mo-title"><?php echo esc_html__( 'Export Configurations', 'miniorange-otp-verification' ); ?></h3>
			<p class="mo-caption my-mo-4"><?php echo esc_html__( 'This tab will help you transfer your plugin configurations when you change your WordPress instance.', 'miniorange-otp-verification' ); ?></p>

			<div class="my-mo-4">
				<label class="flex gap-mo-2 items-center">
					<input type="checkbox" name="mo_export_sensitive_credentials" value="1" />
					<span class="font-semibold"><?php echo esc_html__( 'Export sensitive credentials.', 'miniorange-otp-verification' ); ?></span>
				</label>
				<div class="mo_otp_note mt-mo-4 mb-mo-4 p-mo-3">
					<?php echo esc_html__( 'Note: This will include configured API keys, tokens, secrets, and passwords in the exported JSON file. Enable this only when credentials are needed on the target site.', 'miniorange-otp-verification' ); ?>
				</div>
			</div>

			<input type="submit" class="mo-button primary" value="<?php echo esc_attr__( 'Export configuration', 'miniorange-otp-verification' ); ?>" />
		</form>
	</div>

</div>
