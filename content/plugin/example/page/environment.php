<?php

/**
 * Snappy, a PHP framework for PHP 5.3+
 *
 * @author Alexander Gailey-White <alex@gailey-white.com>
 *
 * This file is part of Snappy.
 *
 * Snappy is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Snappy is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Snappy.  If not, see <http://www.gnu.org/licenses/>.
 */

if( !defined( 'IN_SNAPPY' ) ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit;
}

Document::prependTitle( __( 'Environment'  ) );

/**
 * POST of locale, negotiate with available locales, store in token, and reload page
 */
$locale = Http::post( 'locale' );
if( $locale ) {
	Token::set( 'locale', Language::negotiate( $locale ) );
	Token::saveCookie();
	Http::location( './environment' );
}

/*
 * Get a list of locales from config, if empty add Spanish "es-ES" (see /locale/es-es.php) to test with,
 * and add the default EN for form options
 */
$cfg =& Snappy::getCfg();
$locales = $cfg->get( 'locales' );
if( !$locales ) {
	$locales = array( 'es-ES' );
	$cfg->set( 'locales', $locales );
	$cfg->save();
}
array_unshift( $locales, 'en-GB' );

/**
 * Build form to set locale environment
 */
$form =& Snappy::getForm( 'set_locale' )->method( 'post' );

$form->add( FormField::Radio, 'locale' )
     ->label( __( 'Language' ) )
     ->info( __( 'Select a locale then click Submit to save your preferences.' ) )
     ->options( array_combine( $locales, $locales ) )
     ->value( Language::getLocale() );

$form->add( FormField::InputSubmit )->value( __( 'Submit' ) );

?>
<h3><?php ___( 'Snappy &ndash; Environment settings' ) ?></h3>
<p><a href="./">Return to previous page.</a><br>Here is a basic form created with <code>Snappy::getForm()</code>
	to set the user's locale/language (information is stored and retrieved from a PHP session and browser
	cookie using the <code>Token</code> class). See source of this script.</p>
<?php echo $form;