{if count($errors) > 0}
		<div class="box-16">
			<div class="box">
				<a id="errors_toggle" href="javascript:void(0)">Errors [+]</a>
				<div id="errors" class="box" style="display: none;">
					<table>
						<thead>
							<th>#</th><th>Type</th><th>Error</th><th>Location</th>
						</thead>
						<tbody>
                            {foreach $errors as $error}
							<tr>
								<td>{$error@iteration}</td><td>{$error.errno}</td><td>{$error.errstr}</td><td>{$error.errfile}:{$error.errline}<a class="bt">BT</a></td>
							</tr>
							<tr class="backtrace">
								<td colspan="4"><pre>{$error.backtrace|var_dump}</pre></td>
							</tr>
                            {/foreach}
						</tbody>
					</table>
				</div>
			</div>
		</div>
{/if}