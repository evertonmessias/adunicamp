<?php
$entries          = $this->get_entries();
$form_type        = $this->get_form_type();
$count            = $this->get_total_entries();
$entries_per_page = $this->get_per_page();
$first_item       = $count;
$page_number      = $this->get_paged();

if ( $page_number > 1 ) {
	$first_item = $count - ( ( $page_number - 1 ) * $entries_per_page );
}
?>

<?php foreach ( $entries as $entry ) : ?>

	<tr class="sui-accordion-item">

		<td>
			<label class="sui-checkbox">
				<input name="ids[]" value="<?php echo esc_attr( $entry->entry_id ); ?>" type="checkbox" id="quiz-answer-<?php echo esc_attr( $entry->entry_id ); ?>">
				<span></span>
				<div class="sui-description"><?php echo esc_attr( $first_item ); ?></div>
			</label>
		</td>

		<td colspan="5">
			<?php echo esc_html( date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry->date_created_sql ) ) ); ?>
			<span class="sui-accordion-open-indicator">
							<i class="sui-icon-chevron-down"></i>
						</span>
		</td>

	</tr>

	<tr class="sui-accordion-item-content">

		<td colspan="6">

			<div class="sui-box">

				<div class="sui-box-body fui-entries--knowledge">

					<?php // ROW: Title. ?>
					<div class="fui-entries-block">

						<h2 class="fui-entries-title"><?php echo '#' . esc_attr( $first_item ); ?></h2>

						<p class="sui-description"><?php echo esc_html( date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry->date_created_sql ) ) ); ?></p>

					</div>

					<?php // ROW: Lead Details. ?>
					<?php if ( isset( $entry->meta_data['lead_entry'] ) && isset( $entry->meta_data['lead_entry']['value'] ) ) { ?>

						<div class="fui-entries-block">

							<h3 class="fui-entries-subtitle"><?php esc_html_e( 'Lead Details', 'forminator' ); ?></h3>

							<table class="fui-entries-table" data-design="ghost">

								<tbody>

									<?php foreach ( $entry->meta_data['lead_entry']['value'] as $lead_entry ) { ?>

										<tr>

											<td><?php echo esc_html( $lead_entry['name'] ); ?></td>
											<td><?php echo wp_kses_post( $lead_entry['value'] ); ?></td>

										</tr>

									<?php } ?>

								</tbody>

							</table>

						</div>

					<?php } ?>

					<?php // ROW: Quiz Results. ?>
					<div class="fui-entries-block">

						<h3 class="fui-entries-subtitle"><?php esc_html_e( 'Quiz Results', 'forminator' ); ?></h3>

						<?php if ( 'knowledge' === $form_type ) { ?>

							<?php
							$meta  = $entry->meta_data['entry']['value'];
							$total = count( $meta );
							$right = 0;

							foreach ( $meta as $key => $val ) {
								if ( isset( $val['isCorrect'] ) && boolval( $val['isCorrect'] ) ) {
									$right ++;
								}
							}
							?>

							<p class="sui-description"><?php echo sprintf( esc_html__( 'You got %1$s/%2$s correct answers.', 'forminator' ), intval( $right ), intval( $total ) ); ?></p>

							<table class="fui-entries-table">

								<thead>

									<tr>
										<th><?php esc_html_e( 'Questions', 'forminator' ); ?></th>
										<th><?php esc_html_e( 'Answers', 'forminator' ); ?></th>
									</tr>

								</thead>

								<tbody>

									<?php foreach ( $meta as $answer ) : ?>

										<?php
										$total ++;

										if ( $answer['isCorrect'] ) {
											$right ++;
										}

										if ( isset( $answer['answer'] ) ) {
											$user_answer = $answer['answer'];
										} else {
											$user_answer = $answer['answers'];
										}
										?>

										<tr>
											<td><strong><?php echo esc_html( $answer['question'] ); ?></strong></td>
											<td>
												<?php
												if ( is_array( $user_answer ) ) {
													foreach ( $user_answer as $val ) {
														if ( $answer['isCorrect'] ) {
															echo '<span class="sui-tag sui-tag-success">' . esc_html( $val ) . '</span>';
														} else {
															echo '<span class="sui-tag sui-tag-error">' . esc_html( $val ) . '</span>';
														}
													}
												} else {
													if ( $answer['isCorrect'] ) {
														echo '<span class="sui-tag sui-tag-success">' . esc_html( $user_answer ) . '</span>';
													} else {
														echo '<span class="sui-tag sui-tag-error">' . esc_html( $user_answer ) . '</span>';
													}
												}
												?>
											</td>
										</tr>

									<?php endforeach; ?>

								</tbody>

								<tfoot aria-hidden="true">

									<tr>

										<td colspan="2">

											<div class="fui-entries-table-legend">

												<p class="correct"><?php esc_html_e( 'Correct', 'forminator' ); ?></p>

												<p class="incorrect"><?php esc_html_e( 'Incorrect', 'forminator' ); ?></p>

											</div>

										</td>

									</tr>

								</tfoot>

							</table>

						<?php } else { ?>

							<?php $meta = $entry->meta_data['entry']['value'][0]['value']; ?>

							<?php if ( isset( $meta['answers'] ) && is_array( $meta['answers'] ) ) : ?>

								<table class="fui-entries-table">

									<thead>

										<tr>
											<th><?php esc_html_e( 'Question', 'forminator' ); ?></th>
											<th><?php esc_html_e( 'Answer', 'forminator' ); ?></th>
										</tr>

									</thead>

									<tbody>

										<?php foreach ( $meta['answers'] as $answer ) : ?>

											<tr>
												<td><strong><?php echo esc_html( $answer['question'] ); ?></strong></td>
												<td><?php echo esc_html( $answer['answer'] ); ?></td>
											</tr>

										<?php endforeach; ?>

									</tbody>

									<tfoot aria-hidden="true">

										<tr>

											<td colspan="2"><?php echo wp_kses_post( sprintf( __( '<strong>Quiz Result:</strong> %s', 'forminator' ), $meta['result']['title'] ) ); ?></td>

										</tr>

									</tfoot>

								</table>

							<?php endif; ?>

						<?php } ?>

					</div>

				</div>

			</div>

		</td>

	</tr>

	<?php
	$first_item --;

endforeach;
