<?php
//Settings *************************************************
function portal_page_html()
{ ?>
	<div class="settings-adunicamp">
		<h1 class="title">Configurações da Página Inicial</h1><br>
		<hr>
		<form method="post" action="options.php">
			<?php settings_fields('portal_option_grupo'); ?>


			<!-- Nome ********************************** -->
			<label>
				<h3 class="title">Nome do Site: </h3><input type="text" id="portal_input_0" name="portal_input_0" value="<?php echo get_option('portal_input_0'); ?>" />
			</label>


			<br><br><!-- Logo *************************************** -->
			<hr>
			<?php
			$image1 = get_option('portal_input_1'); ?>
			<h3 class="title">Logo:</h3>
			<table>
				<tr>
					<td><a href="#" onclick="upload_image(1,1);" class="button button-secondary"><?php _e('Upload Image'); ?></a></td>
					<td><input type="text" name="portal_input_1" id="portal_input_1" value="<?php echo $image1; ?>" /></td>
					<td>&emsp;<a href="<?php echo $image1; ?>" target="_blank"><img style="height:30px" id="preview_portal_input_1" alt="preview" title="preview" src="<?php echo $image1; ?>" /></a></td>
				</tr>
			</table>
			<span>(Ideal size: 100x80 px)</span>


			<br><br><!-- Background*************************************** -->
			<hr>
			<?php
			$image2 = get_option('portal_input_2'); ?>
			<h3 class="title">Background:</h3>
			<table>
				<tr>
					<td><a href="#" onclick="upload_image(1,2);" class="button button-secondary"><?php _e('Upload Image'); ?></a></td>
					<td><input type="text" name="portal_input_2" id="portal_input_2" value="<?php echo $image2; ?>" /></td>
					<td>&emsp;<a href="<?php echo $image2; ?>" target="_blank"><img style="height:30px" id="preview_portal_input_2" alt="preview" title="preview" src="<?php echo $image2; ?>" /></a></td>
				</tr>
			</table>
			<span>(Background usado em algumas seções, tamanho ideal: 1920x1000 px)</span>


			<br><br><!-- Colors *************************************** -->
			<hr>

			<label>
				<h3 class="title">Cor do Tema: </h3><input type="color" id="portal_input_3" name="portal_input_3" value="<?php echo get_option('portal_input_3'); ?>" />
			</label>
			<span>(Padrão: #2682A8)</span>

			<br><br><!-- Descrição *************************************** -->
			<hr>
			<label>
				<h3 class="title">Descrição do Slide: </h3><input type="text" id="portal_input_4" name="portal_input_4" value="<?php echo get_option('portal_input_4'); ?>" />
			</label>
			<span>(Frase ao lado do Logo)</span>

			
			<br><br><!-- Sobre ********************************** -->
			<hr>

			<label>
				<h3 class="title">Botões de Acesso (Serviços): </h3>
				<?php
				$portal5 = get_option('portal_input_5');
				if($portal5 == "")$portal5 = file_get_contents(plugin_dir_path(__FILE__) ."buttons.html"); 
				$settings = array( 'textarea_name' => 'portal_input_5','tinymce' => false, 'media_buttons' => false, 'quicktags' => false, 'wpautop' => false );
				wp_editor($portal5, 'portal_about_box', $settings);
				?>				
			</label>
			<span>(Para voltar ao estado inicial limpe o editor)</span>

			<br><br><!-- Endereço *************************************** -->
			<hr>
			<label>
				<h3 class="title">Endereço: </h3><input type="text" id="portal_input_6" name="portal_input_6" value="<?php echo get_option('portal_input_6'); ?>" />
			</label>
			<br>

			<br><br><!-- Maps *************************************** -->
			<hr>
			<label>
				<h3 class="title">Google Maps: </h3><input type="text" id="portal_input_7" name="portal_input_7" value="<?php echo get_option('portal_input_7'); ?>" />
			</label>
			<br><span>(https://www.google.com/maps/embed?......)</span>			

			<br><br><!-- Fone *************************************** -->
			<hr>
			<label>
				<h3 class="title">Telefone: </h3><input type="text" id="portal_input_9" name="portal_input_9" value="<?php echo get_option('portal_input_9'); ?>" />
			</label>
			<br><span>(+00 00 00000-0000)</span>


			<br><br><!-- E-Mail *************************************** -->
			<hr>
			<label>
				<h3 class="title">E-Mail: </h3><input type="email" id="portal_input_10" name="portal_input_10" value="<?php echo get_option('portal_input_10'); ?>" />
			</label>
			<br><span>(only one)</span>	
			
			<br><br><!-- Facebook *************************************** -->
			<hr>
			<label>
				<h3 class="title">Facebook: </h3><input type="text" id="portal_input_11" name="portal_input_11" value="<?php echo get_option('portal_input_11'); ?>" />
			</label>
			

			<br><br><!-- Instagram *************************************** -->
			<hr>
			<label>
				<h3 class="title">Instagram: </h3><input type="text" id="portal_input_12" name="portal_input_12" value="<?php echo get_option('portal_input_12'); ?>" />
			</label>

			<br><br><!-- YouTube *************************************** -->
			<hr>
			<label>
				<h3 class="title">YouTube: </h3><input type="text" id="portal_input_13" name="portal_input_13" value="<?php echo get_option('portal_input_13'); ?>" />
			</label>

			<br><br><!-- Twitter *************************************** -->
			<hr>
			<label>
				<h3 class="title">Twitter: </h3><input type="text" id="portal_input_14" name="portal_input_14" value="<?php echo get_option('portal_input_14'); ?>" />
			</label>								
			
			<br><br><!-- *************************************** -->
			<hr>	

			<?php submit_button(); ?>
		</form>
	</div>
<?php
}

function portal_options_page()
{
	add_submenu_page('adunicamp', 'Pagina Inicial', 'Pagina Inicial', 'edit_posts', 'pagina-inicial', 'portal_page_html', 1);
}
add_action('admin_menu', 'portal_options_page');



//************************ DB Fields

function portal_settings0()
{
	add_option('portal_input_0');
	register_setting('portal_option_grupo', 'portal_input_0');
}
add_action('admin_init', 'portal_settings0');

function portal_settings1()
{
	add_option('portal_input_1');
	register_setting('portal_option_grupo', 'portal_input_1');
}
add_action('admin_init', 'portal_settings1');

function portal_settings2()
{
	add_option('portal_input_2');
	register_setting('portal_option_grupo', 'portal_input_2');
}
add_action('admin_init', 'portal_settings2');

function portal_settings3()
{
	add_option('portal_input_3');
	register_setting('portal_option_grupo', 'portal_input_3');
}
add_action('admin_init', 'portal_settings3');

function portal_settings4()
{
	add_option('portal_input_4');
	register_setting('portal_option_grupo', 'portal_input_4');
}
add_action('admin_init', 'portal_settings4');

function portal_settings5()
{
	add_option('portal_input_5');
	register_setting('portal_option_grupo', 'portal_input_5');
}
add_action('admin_init', 'portal_settings5');

function portal_settings6()
{
	add_option('portal_input_6');
	register_setting('portal_option_grupo', 'portal_input_6');
}
add_action('admin_init', 'portal_settings6');

function portal_settings7()
{
	add_option('portal_input_7');
	register_setting('portal_option_grupo', 'portal_input_7');
}
add_action('admin_init', 'portal_settings7');


function portal_settings8()
{
	add_option('portal_input_8');
	register_setting('portal_option_grupo', 'portal_input_8');
}
add_action('admin_init', 'portal_settings8');


function portal_settings9()
{
	add_option('portal_input_9');
	register_setting('portal_option_grupo', 'portal_input_9');
}
add_action('admin_init', 'portal_settings9');


function portal_settings10()
{
	add_option('portal_input_10');
	register_setting('portal_option_grupo', 'portal_input_10');
}
add_action('admin_init', 'portal_settings10');


function portal_settings11()
{
	add_option('portal_input_11');
	register_setting('portal_option_grupo', 'portal_input_11');
}
add_action('admin_init', 'portal_settings11');


function portal_settings12()
{
	add_option('portal_input_12');
	register_setting('portal_option_grupo', 'portal_input_12');
}
add_action('admin_init', 'portal_settings12');


function portal_settings13()
{
	add_option('portal_input_13');
	register_setting('portal_option_grupo', 'portal_input_13');
}
add_action('admin_init', 'portal_settings13');


function portal_settings14()
{
	add_option('portal_input_14');
	register_setting('portal_option_grupo', 'portal_input_14');
}
add_action('admin_init', 'portal_settings14');