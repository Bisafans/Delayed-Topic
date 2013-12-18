{if $board->getModeratorPermission('canEnableThread')}
	<dl>
		<dt></dt>
		<dd><label><input type="checkbox" id="delayedEnable" name="delayedEnable" value="1"{if $delayedEnable} checked="checked"{/if} /> {lang}wbb.delayed.enable{/lang}</label></dd>
	</dl>
	<dl>
		<dt><label for="delayedTime">{lang}wbb.delayed.timeout{/lang}</label></dt>
		<dd>
			<input type="datetime" id="delayedTime" name="delayedTime" value="{if $delayedTime}{@'Y-m-d H:i'|gmdate:$delayedTime}{/if}" />
			
			{if $errorField == 'delayedTime'}
				<small class="innerError">
					{if $errorType == 'notValid'}{lang}wbb.delayed.notValid{/lang}{/if}
				</small>
			{/if}
		</dd>
	</dl>
{/if}