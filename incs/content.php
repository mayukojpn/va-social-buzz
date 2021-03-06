<?php
/**
 * WordPress plugin content class.
 *
 * @package    WordPress
 * @subpackage VA Social Buzz
 * @author     KUCKLU <kuck1u@visualive.jp>
 *             Copyright (C) 2015 KUCKLU and VisuAlive.
 *             This program is free software; you can redistribute it and/or modify
 *             it under the terms of the GNU General Public License as published by
 *             the Free Software Foundation; either version 2 of the License, or
 *             (at your option) any later version.
 *             This program is distributed in the hope that it will be useful,
 *             but WITHOUT ANY WARRANTY; without even the implied warranty of
 *             MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *             GNU General Public License for more details.
 *             You should have received a copy of the GNU General Public License along
 *             with this program; if not, write to the Free Software Foundation, Inc.,
 *             51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *             It is also available through the world-wide-web at this URL:
 *             http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace VASOCIALBUZZ\Modules;

use VASOCIALBUZZ\VASOCIALBUZZ_Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Content.
 *
 * @since 0.0.1 (Alpha)
 */
class VASOCIALBUZZ_Content extends VASOCIALBUZZ_Singleton {
	/**
	 * This hook is called once any activated themes have been loaded.
	 *
	 * @since 0.0.1 (Alpha)
	 *
	 * @param array $settings If the set value is required, pass a value in an array.
	 */
	public function __construct( $settings = array() ) {
		$priority = 10;

		add_image_size( self::$prefix . '-thumbnail', '980', '9999', false );

		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_script' ) );
		add_filter( 'the_content', array( &$this, 'the_content' ), (int) $priority );
	}

	/**
	 * Create content.
	 *
	 * @since 0.0.1 (Alpha)
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function the_content( $content ) {
		$content = array(
			$content,
			self::_content_template(),
		);

		return implode( PHP_EOL, $content );
	}

	/**
	 * Echo scripts.
	 */
	public function wp_enqueue_script() {
		$localize['locale'] = esc_attr( self::_get_locale() );

		if ( isset( get_option( 'va_social_buzz' )['fb_appid'] ) && ! empty( get_option( 'va_social_buzz' )['fb_appid'] ) ) {
			$localize['appid'] = esc_attr( self::get_option()['fb_appid'] );
		}

		wp_enqueue_style( 'va-social-buzz', VASOCIALBUZZ_URL . 'assets/css/style.css' );
		wp_enqueue_script( 'va-social-buzz', VASOCIALBUZZ_URL . 'assets/js/script.js', array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'va-social-buzz', 'vaSocialBuzzSettings', $localize );
	}

	/**
	 * Content template.
	 *
	 * @todo 後で分割する
	 *
	 * @since 0.0.1 (Alpha)
	 * @return string
	 */
	protected function _content_template() {
		if ( ! is_singular() ) {
			return null;
		}

		$options = self::get_option();

		if ( has_post_thumbnail() && ! post_password_required() ) {
			$options['thumb'] = get_the_post_thumbnail_url( null, self::$prefix . '-thumbnail' );
		} elseif ( has_site_icon() ) {
			$options['thumb'] = get_site_icon_url();
		} else {
			$options['thumb'] = '';
		}

		$template[] = '<div id="va-social-buzz" class="va-social-buzz">';

		if ( ! empty( $options['fb_page'] ) ) {
			$template[] = '<div class="vasb_fb">';
			$template[] = sprintf(
				'<div class="vasb_fb_thumbnail" style="background-image: url(%s);"></div>',
				$options['thumb']
			);
			$template[] = '<div class="vasb_fb_like">';
			$template[] = sprintf(
				'<p>%s<br>%s</p>',
				esc_html( $options['text']['like'][0] ),
				esc_html( $options['text']['like'][1] )
			);
			$template[] = sprintf(
				'<div class="fb-like" data-href="https://www.facebook.com/%s" data-layout="button_count" data-action="like" data-show-faces="false" data-share="false"></div>',
				esc_attr( $options['fb_page'] )
			);
			$template[] = '</div>';
			$template[] = '<!-- //.vasb_fb --></div>';
		}

		$template[] = '<div class="vasb_share">';
		$template[] = '<div class="vasb_share_button vasb_share_button-fb">';
		$template[] = sprintf(
			'<a href="http://www.facebook.com/sharer.php?u=%s"><i class="vasb_icon"></i><span>%s</span></a>',
			get_the_permalink(),
			esc_html( $options['text']['share'] )
		);
		$template[] = '</div>';
		$template[] = '<div class="vasb_share_button vasb_share_button-tw">';
		$template[] = sprintf(
			'<a href="http://twitter.com/share?url=%s&text=%s"><i class="vasb_icon"></i><span>%s</span></a>',
			get_the_permalink(),
			get_the_title(),
			esc_html( $options['text']['tweet'] )
		);
		$template[] = '</div>';
		$template[] = '<!-- //.vasb_share --></div>';

		if ( ! empty( $options['tw_account'] ) ) {
			$template[] = '<div class="vasb_tw">';
			$template[] = sprintf(
				'%1$s <a href="https://twitter.com/%2$s" class="twitter-follow-button" data-show-count="true" data-size="large" data-show-screen-name="false">Follow @%2$s</a>',
				esc_html( $options['text']['follow'] ),
				esc_html( $options['tw_account'] )
			);
			$template[] = '<!-- //.vasb_tw --></div>';
		}

		$template[] = '<!-- //.va-social-buzz --></div>';

		return apply_filters( 'va_social_buzz_content', implode( PHP_EOL, $template ), $options );
	}

	/**
	 * Output the locale, doing some conversions to make sure the proper Facebook locale is outputted.
	 * Yoast SEO Plugin Thanks ! https://yoast.com/wordpress/plugins/seo/
	 *
	 * @see  http://www.facebook.com/translations/FacebookLocales.xml for the list of supported locales
	 * @link https://developers.facebook.com/docs/reference/opengraph/object-type/article/
	 * @return string $locale
	 */
	protected function _get_locale() {
		$locale     = apply_filters( 'va_social_buzz_locale', get_locale() );
		$locales    = apply_filters(
			'va_social_buzz_locales', array(
				'ca' => 'ca_ES',
				'en' => 'en_US',
				'el' => 'el_GR',
				'et' => 'et_EE',
				'ja' => 'ja_JP',
				'sq' => 'sq_AL',
				'uk' => 'uk_UA',
				'vi' => 'vi_VN',
				'zh' => 'zh_CN',
			)
		);
		$fb_locales = apply_filters(
			'va_social_buzz_fb_locales', array(
				'af_ZA', // Afrikaans.
				'ar_AR', // Arabic.
				'az_AZ', // Azerbaijani.
				'be_BY', // Belarusian.
				'bg_BG', // Bulgarian.
				'bn_IN', // Bengali.
				'bs_BA', // Bosnian.
				'ca_ES', // Catalan.
				'cs_CZ', // Czech.
				'cx_PH', // Cebuano.
				'cy_GB', // Welsh.
				'da_DK', // Danish.
				'de_DE', // German.
				'el_GR', // Greek.
				'en_GB', // English (UK).
				'en_PI', // English (Pirate).
				'en_UD', // English (Upside Down).
				'en_US', // English (US).
				'eo_EO', // Esperanto.
				'es_ES', // Spanish (Spain).
				'es_LA', // Spanish.
				'et_EE', // Estonian.
				'eu_ES', // Basque.
				'fa_IR', // Persian.
				'fb_LT', // Leet Speak.
				'fi_FI', // Finnish.
				'fo_FO', // Faroese.
				'fr_CA', // French (Canada).
				'fr_FR', // French (France).
				'fy_NL', // Frisian.
				'ga_IE', // Irish.
				'gl_ES', // Galician.
				'gn_PY', // Guarani.
				'gu_IN', // Gujarati.
				'he_IL', // Hebrew.
				'hi_IN', // Hindi.
				'hr_HR', // Croatian.
				'hu_HU', // Hungarian.
				'hy_AM', // Armenian.
				'id_ID', // Indonesian.
				'is_IS', // Icelandic.
				'it_IT', // Italian.
				'ja_JP', // Japanese.
				'ja_KS', // Japanese (Kansai).
				'jv_ID', // Javanese.
				'ka_GE', // Georgian.
				'kk_KZ', // Kazakh.
				'km_KH', // Khmer.
				'kn_IN', // Kannada.
				'ko_KR', // Korean.
				'ku_TR', // Kurdish.
				'la_VA', // Latin.
				'lt_LT', // Lithuanian.
				'lv_LV', // Latvian.
				'mk_MK', // Macedonian.
				'ml_IN', // Malayalam.
				'mn_MN', // Mongolian.
				'mr_IN', // Marathi.
				'ms_MY', // Malay.
				'nb_NO', // Norwegian (bokmal).
				'ne_NP', // Nepali.
				'nl_NL', // Dutch.
				'nn_NO', // Norwegian (nynorsk).
				'pa_IN', // Punjabi.
				'pl_PL', // Polish.
				'ps_AF', // Pashto.
				'pt_BR', // Portuguese (Brazil).
				'pt_PT', // Portuguese (Portugal).
				'ro_RO', // Romanian.
				'ru_RU', // Russian.
				'si_LK', // Sinhala.
				'sk_SK', // Slovak.
				'sl_SI', // Slovenian.
				'sq_AL', // Albanian.
				'sr_RS', // Serbian.
				'sv_SE', // Swedish.
				'sw_KE', // Swahili.
				'ta_IN', // Tamil.
				'te_IN', // Telugu.
				'tg_TJ', // Tajik.
				'th_TH', // Thai.
				'tl_PH', // Filipino.
				'tr_TR', // Turkish.
				'uk_UA', // Ukrainian.
				'ur_PK', // Urdu.
				'uz_UZ', // Uzbek.
				'vi_VN', // Vietnamese.
				'zh_CN', // Simplified Chinese (China).
				'zh_HK', // Traditional Chinese (Hong Kong).
				'zh_TW', // Traditional Chinese (Taiwan).
			)
		);

		// Convert locales like "en" to "en_US", in case that works for the given locale (sometimes it does).
		if ( isset( $locales[ $locale ] ) ) {
			$locale = $locales[ $locale ];
		} elseif ( strlen( $locale ) == 2 ) {
			$locale = strtolower( $locale ) . '_' . strtoupper( $locale );
		} else {
			$locale = strtolower( substr( $locale, 0, 2 ) ) . '_' . strtoupper( substr( $locale, 0, 2 ) );
		}

		// Check to see if the locale is a valid FB one, if not, use en_US as a fallback.
		if ( ! in_array( $locale, $fb_locales ) ) {
			$locale = 'en_US';
		}

		return $locale;
	}
}
