<?php
//Settings *************************************************
function portal_page_html()
{ ?>
	<div class="settings-adunicamp">
		<h1 class="title">Configurações da Página Inicial</h1><br>
		<hr>
		<i id="scrollToTop" class='bx bxs-up-arrow-square'></i>
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
				<h3 class="title">Descrição do Topo: </h3><input type="text" id="portal_input_4" name="portal_input_4" value="<?php echo get_option('portal_input_4'); ?>" />
			</label>
			<span>(Frase do Topo)</span>


			<br><br><!-- Botão Top******************************* -->
			<hr>
			<label>
				<h3 class="title">Botão Top: </h3><input type="text" id="portal_input_41" name="portal_input_41" value="<?php echo get_option('portal_input_41'); ?>" />
			</label>
			<span>(<b>URL</b> e <b>Título</b>, separados por <b>vírgula</b>, ex: /filie-se,FILIE-SE)</span>


			<br><br><!-- Tipo e Número de Slides******************************* -->
			<hr>			
				<h3 class="title">Tipo e Número de Slides: </h3>
				
				<label><input type="radio" class="portal_input_46 tabs" name="portal_input_46" value="tabs" <?php checked( 'tabs', get_option('portal_input_46') ); ?> />&ensp;Slide Tabs (3 slides)</label>				
				<label><input type="radio" class="portal_input_46 full" name="portal_input_46" value="full" <?php checked( 'full', get_option('portal_input_46') ); ?> />&ensp;Slide Full&ensp;(n slides)</label>

				<input type="number" class="nslide" onKeyDown="return false" min="1" max="10" id="portal_input_42" name="portal_input_42" value="<?php echo get_option('portal_input_42'); ?>" />
			

			<br><!-- Categoria dos Slides******************************* -->
			<label>
				<h3 class="title">Categoria dos Slides: </h3>
				<select id="portal_input_43" name="portal_input_43">
					<option selected hidden><?php echo get_option('portal_input_43'); ?></option>
					<?php
					$argsCat = array(
						'post_type' => 'post',
						'orderby'       => 'name',
						'order'         => 'ASC'
					);
					$categories = get_terms('category', $argsCat);
					foreach ($categories as $category) {
						echo '<option>' . $category->slug . '</option>';
					}
					?>
				</select>
			</label>
			<br><span>(Altera qual a <b>Categoria</b> do Post é exibida nos Slides)</span>


			<br><br><!-- Destaques******************************* -->
			<hr>
			<label>
				<h3 class="title">Categoria dos Destaques: </h3>
				<select id="portal_input_45" name="portal_input_45">
					<option selected hidden><?php echo get_option('portal_input_45'); ?></option>
					<?php
					$argsCat = array(
						'post_type' => 'post',
						'orderby'       => 'name',
						'order'         => 'ASC'
					);
					$categories = get_terms('category', $argsCat);
					foreach ($categories as $category) {
						echo '<option>' . $category->slug . '</option>';
					}
					?>
				</select>
			</label>
			<br><span>(Altera qual a <b>Categoria</b> do Post é exibida nos Destaques)</span>


			<br><br><!-- Botões de Acesso (Serviços) ********************************** -->
			<hr>
			<h3 class="title">Botões de Acesso (Serviços): </h3>			
			<br>
			<label>
				<strong>Botão de Acesso 1: </strong><input type="text" id="portal_input_51" name="portal_input_51" value="<?php echo get_option('portal_input_51'); ?>" />
			</label>
			<br>
			<label>
				<strong>Botão de Acesso 2: </strong><input type="text" id="portal_input_52" name="portal_input_52" value="<?php echo get_option('portal_input_52'); ?>" />
			</label>

			<br>
			<label>
				<strong>Botão de Acesso 3: </strong><input type="text" id="portal_input_53" name="portal_input_53" value="<?php echo get_option('portal_input_53'); ?>" />
			</label>

			<br>
			<label>
				<strong>Botão de Acesso 4: </strong><input type="text" id="portal_input_54" name="portal_input_54" value="<?php echo get_option('portal_input_54'); ?>" />
			</label>

			<br>
			<label>
				<strong>Botão de Acesso 5: </strong><input type="text" id="portal_input_55" name="portal_input_55" value="<?php echo get_option('portal_input_55'); ?>" />
			</label>

			<br>
			<label>
				<strong>Botão de Acesso 6: </strong><input type="text" id="portal_input_56" name="portal_input_56" value="<?php echo get_option('portal_input_56'); ?>" />
			</label>

			<br>
			<label>
				<strong>Botão de Acesso 7: </strong><input type="text" id="portal_input_57" name="portal_input_57" value="<?php echo get_option('portal_input_57'); ?>" />
			</label>

			<br>
			<label>
				<strong>Botão de Acesso 8: </strong><input type="text" id="portal_input_58" name="portal_input_58" value="<?php echo get_option('portal_input_58'); ?>" />
			</label>


			<br><span>(<b>URL, Título e Cor</b>, separados por <b>vírgula</b>, ex: /adunicamp-solidaria,ADUNICAMP SOLIDÁRIA,#f00)</span>



			<br><br><!-- Endereço *************************************** -->
			<hr>
			<label>
				<h3 class="title">Endereço: </h3><input type="text" id="portal_input_6" name="portal_input_6" value="<?php echo get_option('portal_input_6'); ?>" />
			</label>

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

function portal_settings()
{
	add_option('portal_input_0');
	register_setting('portal_option_grupo', 'portal_input_0');

	add_option('portal_input_1');
	register_setting('portal_option_grupo', 'portal_input_1');

	add_option('portal_input_2');
	register_setting('portal_option_grupo', 'portal_input_2');

	add_option('portal_input_3');
	register_setting('portal_option_grupo', 'portal_input_3');

	add_option('portal_input_4');
	register_setting('portal_option_grupo', 'portal_input_4');

	add_option('portal_input_41');
	register_setting('portal_option_grupo', 'portal_input_41');

	add_option('portal_input_42');
	register_setting('portal_option_grupo', 'portal_input_42');

	add_option('portal_input_43');
	register_setting('portal_option_grupo', 'portal_input_43');

	add_option('portal_input_44');
	register_setting('portal_option_grupo', 'portal_input_44');

	add_option('portal_input_45');
	register_setting('portal_option_grupo', 'portal_input_45');

	add_option('portal_input_46');
	register_setting('portal_option_grupo', 'portal_input_46');

	add_option('portal_input_47');
	register_setting('portal_option_grupo', 'portal_input_47');

	add_option('portal_input_48');
	register_setting('portal_option_grupo', 'portal_input_48');

	add_option('portal_input_49');
	register_setting('portal_option_grupo', 'portal_input_49');

	add_option('portal_input_50');
	register_setting('portal_option_grupo', 'portal_input_50');

	add_option('portal_input_51');
	register_setting('portal_option_grupo', 'portal_input_51');

	add_option('portal_input_52');
	register_setting('portal_option_grupo', 'portal_input_52');

	add_option('portal_input_53');
	register_setting('portal_option_grupo', 'portal_input_53');

	add_option('portal_input_54');
	register_setting('portal_option_grupo', 'portal_input_54');

	add_option('portal_input_55');
	register_setting('portal_option_grupo', 'portal_input_55');

	add_option('portal_input_56');
	register_setting('portal_option_grupo', 'portal_input_56');

	add_option('portal_input_57');
	register_setting('portal_option_grupo', 'portal_input_57');

	add_option('portal_input_58');
	register_setting('portal_option_grupo', 'portal_input_58');

	add_option('portal_input_6');
	register_setting('portal_option_grupo', 'portal_input_6');

	add_option('portal_input_7');
	register_setting('portal_option_grupo', 'portal_input_7');

	add_option('portal_input_8');
	register_setting('portal_option_grupo', 'portal_input_8');

	add_option('portal_input_9');
	register_setting('portal_option_grupo', 'portal_input_9');

	add_option('portal_input_10');
	register_setting('portal_option_grupo', 'portal_input_10');

	add_option('portal_input_11');
	register_setting('portal_option_grupo', 'portal_input_11');

	add_option('portal_input_12');
	register_setting('portal_option_grupo', 'portal_input_12');

	add_option('portal_input_13');
	register_setting('portal_option_grupo', 'portal_input_13');

	add_option('portal_input_14');
	register_setting('portal_option_grupo', 'portal_input_14');
}
add_action('admin_init', 'portal_settings');
