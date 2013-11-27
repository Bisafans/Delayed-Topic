{if $board->getModeratorPermission('canEnableThread')}
	<dl>
		<dt></dt>
		<dd><label><input type="checkbox" id="delayedEnable" name="delayedEnable" value="1" /> {lang}wbb.delayed.enable{/lang}</label></dd>
	</dl>
	<dl>
		<dt><label for="delayedEnableTime">{lang}wbb.delayed.timeout{/lang}</label></dt>
		<dd><input type="datetime" id="delayedEnableTime" name="delayedEnableTime" value="" /></dd>
	</dl>
{/if}