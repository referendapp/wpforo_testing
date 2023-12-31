<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;
if( ! WPF()->usergroup->can( 'mth' ) ) exit;
?>

<div id="wpf-admin-wrap" class="wrap wpforo-themes">
    <h2 style="padding:20px 0 0; line-height: 20px; margin-bottom:15px;">
		<?php _e( 'Forum Themes', 'wpforo' ); ?>
    </h2>
	<?php WPF()->notice->show() ?>

    <div style="box-shadow:none; margin:25px 0 20px;" class="wpf-info-bar">
        <p style="font-size:13px; padding:0; margin:10px;">
            wpForo theme files contain the markup and template structure for frontend of your forum.
            Theme files can be found within the <span style="color:#43A6DF">/wpforo/themes/</span> directory, in current active theme folder, for example <span style="color:#43A6DF">/classic/</span>.
            You can edit these files in an upgrade-safe way using overrides.
            For example, copy the certain or all files of <span style="color:#43A6DF">/classic/</span> folder into a folder within your current active WordPress theme named <span style="color:#43A6DF">/wpforo/</span>, keeping the same file structure.<br>
        </p>
        <div style="background:#F5F5F5; border:#ddd 1px dotted; padding:10px 15px; margin:5px 0; font-size:13px; line-height:18px;">Example: To override the "Topic List" template file of the Extended (#1) forum layout, copy according file: <span style="color:#43A6DF">plugins/wpforo/themes/classic/<span style="color:#C98B4C">layouts/1/topic.php</span></span> to <span style="color:#43A6DF">themes/yourtheme/wpforo/<span style="color:#C98B4C">layouts/1/topic.php</span></span></div>
        <p style="font-size:13px; padding:0; margin:10px;">
            The copied file will now automatically override the wpForo default theme file. All changes in this file will not be lost on forum update.
        </p>
        <div style="background:#F6F7D8; border:#ddd 1px dotted; padding:10px 15px; margin:5px 0; font-size:13px; line-height:18px;">Do not edit these files within the core plugin itself as they are overwritten during the upgrade process and any customizations will be lost.</div>
    </div>

	<?php
	$themes                = WPF()->tpl->find_themes();
	if( ! empty( $themes ) ) :
        $_tk = WPF()->tpl->theme . '/style.css';
        $_t = $themes[$_tk];
        unset( $themes[$_tk] );
        $themes = array_merge( [ $_tk => $_t], $themes );
		foreach( $themes as $main_file => $theme ):
			$theme_folder = trim( basename( dirname( (string) $main_file ) ), '/' );
			$theme_url     = WPFORO_THEME_URL . '/' . $theme_folder;
			$layouts       = WPF()->tpl->find_themes( '/' . $theme_folder . '/layouts', 'php', 'layout' );
			$styles        = WPF()->tpl->find_styles( $theme_folder );
			$is_active     = WPF()->tpl->theme === $theme_folder;
			$theme_archive = wpforo_get_option( 'theme_archive_' . $theme_folder, [], false );
			$has_archive   = ! empty( $theme_archive );
			?>
            <div class="wpf-div-table">
                <div class="wpf-div-tr">
                    <div class="wpf-div-td" style="width:50%; border-bottom:1px dotted #ddd;">
						<?php if( $is_active ): ?>
                            <span style="color:#279800; font-weight:bold; text-transform:uppercase;"><?php _e( 'Current active theme', 'wpforo' ); ?></span>
						<?php else: ?>
                            <span style="color:#555555; font-weight:bold; text-transform:uppercase;"><?php _e( 'Inactive', 'wpforo' ); ?></span>
						<?php endif; ?>
                    </div>
                    <div class="wpf-div-td" style="width:50%; border-bottom:1px dotted #ddd;">
						<?php _e( 'LAYOUTS', 'wpforo' ); ?> (<?php echo count( $layouts ) ?>)
                    </div>
                </div>
                <div class="wpf-div-tr">
                    <div class="wpf-div-td" style="width:60%;">
                        <div class="wpf-theme-screenshot" style="background:url('<?php echo esc_url( (string) $theme_url ) ?>/screenshot.png') 0 0 no-repeat;background-size: cover;"></div>
                        <div class="wpf-theme-info">
                            <h3 style="margin-top:5px; margin-bottom:10px;"><?php echo esc_html( wpforo_text( $theme['name']['value'], 30, false ) ) ?> | <?php echo ( $theme['version']['value'] ) ? 'version ' . esc_html( $theme['version']['value'] ) : ''; ?></h3>
                            <p style="font-size:14px;" title="<?php echo esc_attr( $theme['author']['value'] ) ?>"><?php echo ( $theme['author']['value'] ) ? '<strong>Author:</strong>&nbsp; ' . esc_html( wpforo_text( $theme['author']['value'], 30, false ) ) : ''; ?></p>
                            <p style="font-size:14px;" title="<?php echo esc_attr( $theme['theme_url']['value'] ) ?>"><?php echo ( $theme['theme_url']['value'] ) ? '<strong>URI:</strong>&nbsp; <a href="' . esc_url( (string) $theme['theme_url']['value'] ) . '" target="_blank">' . mb_substr( (string) $theme['theme_url']['value'], 0, 30 ) . '</a>' : ''; ?></p>
                            <p style="margin-top:5px;"><?php echo ( $theme['description']['value'] ) ? esc_html( wpforo_text( $theme['description']['value'], 200, false ) ) : ''; ?></p>
                        </div>
                        <div class="wpf-theme-actions">
							<?php if( count( $themes ) > 1 && ! $is_active ): ?>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=wpforo-themes&wpfaction=theme_activate&theme=' . sanitize_text_field( $theme_folder ) ), 'wpforo-theme-activate' ) ?>" class="wpf-action button"><?php _e( 'Activate', 'wpforo' ); ?></a>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=wpforo-themes&wpfaction=theme_delete&theme=' . sanitize_text_field( $theme_folder ) ), 'wpforo-theme-delete' ) ?>" class="wpf-delete button" onclick="return confirm('<?php _e( 'Are you sure you want to delete this theme files?' ) ?>')"><?php _e( 'Delete', 'wpforo' ); ?></a>
							<?php endif; ?>
                        </div>
                    </div>
                    <div class="wpf-div-td" style="width:40%; border-left: 1px dotted #ddd;">
						<?php
						if( ! empty( $layouts ) ) {
							foreach( $layouts as $layout ) {
								?>
                                <div class="wpf-layout-info" style="display:block; border-bottom:1px dotted #ddd; padding:0 0 10px; margin:0 0 10px;">
                                    <h4 style="margin:1px 0;"><?php echo esc_html( wpforo_text( $layout['name']['value'], 30, false ) ) ?> <?php echo ( $layout['version']['value'] ) ? '(' . esc_html( $layout['version']['value'] ) . ')' : ''; ?> | <?php echo ( $layout['author']['value'] ) ? '<a href="' . esc_url( (string) $layout['layout_url']['value'] ) . '" target="_blank">' . esc_html( wpforo_text( $layout['author']['value'], 25, false ) ) . '</a>' : '' ?></h4>
                                    <p><?php echo ( $layout['description']['value'] ) ? esc_html( wpforo_text( $layout['description']['value'], 120, false ) ) : ''; ?></p>
                                </div>
								<?php
							}
						} else {
							?>
                            <div class="wpf-layout-info"><p style="text-align:center;"><? _e( 'No layout found', 'wpforo' ); ?></p></div><?php
						}
						?>
                    </div>
                </div>
            </div>
		<?php endforeach; ?>
	<?php else: ?>
        <div class="wpf-div-table">
            <div class="wpf-div-tr">
                <div class="wpf-div-td">
                    <p style="text-align:center;"><?php _e( 'No theme found', 'wpforo' ); ?></p>
                </div>
            </div>
        </div>
	<?php endif; ?>
</div>
