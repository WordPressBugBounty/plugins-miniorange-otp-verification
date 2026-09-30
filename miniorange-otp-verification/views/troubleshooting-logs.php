<?php
/**
 * Admin view for the Troubleshooting > Logs subtab.
 *
 * @var string $nonce               Nonce action string for the settings/clear forms.
 * @var string $hidden              'hidden' CSS class when this subtab isn't active.
 * @var bool   $debug_logs_enabled  Whether debug logging is currently enabled.
 * @var bool   $log_file_exists     Whether a log file currently exists.
 * @var string $log_file_size       Human-readable log file size.
 * @var string $log_last_modified   Last-modified timestamp of the log file.
 * @var string $download_log_url    Nonce-signed URL to download the log file.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="logsSubTabContainer" class="mo-subpage-container <?php echo esc_attr( $hidden ); ?>">

	<form name="mo_troubleshooting_logs_form" method="post" action="">
		<?php wp_nonce_field( $nonce ); ?>
		<input type="hidden" name="option" value="mo_save_troubleshooting_logs" />

		<div class="mo-header">
			<p class="mo-heading flex-1"><?php echo esc_html__( 'Logs', 'miniorange-otp-verification' ); ?></p>
			<input type="submit" name="save" class="mo-button inverted" value="<?php echo esc_attr__( 'Save Settings', 'miniorange-otp-verification' ); ?>" />
		</div>

		<div class="border-b flex flex-col gap-mo-6 px-mo-4">
			<div class="w-full flex m-mo-4">
				<div class="flex-1">
					<h5 class="mo-title"><?php echo esc_html__( 'Debug Logs', 'miniorange-otp-verification' ); ?></h5>
					<p class="mo-caption mt-mo-2"><?php echo esc_html__( 'Enable error logs to record API responses when OTPs are sent. Useful for diagnosing delivery failures.', 'miniorange-otp-verification' ); ?></p>
				</div>
				<div class="flex-1">
					<div class="my-mo-4">
						<input type="checkbox" name="mo_enable_debug_logs" id="mo_enable_debug_logs" value="1" <?php echo checked( $debug_logs_enabled, true, false ); ?> />
						<label for="mo_enable_debug_logs"><?php echo esc_html__( 'Enable error logs', 'miniorange-otp-verification' ); ?></label>
					</div>
				</div>
			</div>
		</div>
	</form>

	<?php if ( $log_file_exists ) : ?>
	<form name="mo_clear_logs_form" method="post" action="">
		<?php wp_nonce_field( $nonce ); ?>
		<input type="hidden" name="option" value="mo_clear_debug_logs" />

		<div class="border-b flex flex-col gap-mo-6 px-mo-4 py-mo-4">
			<div class="w-full flex items-center m-mo-4 gap-mo-8">
				<div class="flex-1">
					<h5 class="mo-title"><?php echo esc_html__( 'Log File', 'miniorange-otp-verification' ); ?></h5>
					<p class="mo-caption mt-mo-2">
						<?php echo esc_html__( 'Size', 'miniorange-otp-verification' ); ?>: <strong><?php echo esc_html( $log_file_size ); ?></strong>
						&nbsp;&nbsp;|&nbsp;&nbsp;
						<?php echo esc_html__( 'Last Modified', 'miniorange-otp-verification' ); ?>: <strong><?php echo esc_html( $log_last_modified ); ?></strong>
					</p>
				</div>
				<div class="flex gap-mo-4">
					<a href="<?php echo esc_url( $download_log_url ); ?>" class="mo-button inverted"><?php echo esc_html__( 'Download Log', 'miniorange-otp-verification' ); ?></a>
					<input
						type="submit"
						name="clear_logs"
						class="mo-button alert"
						value="<?php echo esc_attr__( 'Clear Logs', 'miniorange-otp-verification' ); ?>"
						onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all debug logs?', 'miniorange-otp-verification' ) ); ?>');" />
				</div>
			</div>
		</div>
	</form>
	<?php else : ?>
	<div class="px-mo-4 py-mo-4 border-b">
		<div class="m-mo-4">
			<p class="mo-caption"><?php echo esc_html__( 'No log file found. Enable debug logging above and perform any plugin action to start capturing logs.', 'miniorange-otp-verification' ); ?></p>
		</div>
	</div>
	<?php endif; ?>

</div>
