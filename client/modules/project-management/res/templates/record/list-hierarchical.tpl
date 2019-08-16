{{#if noData}}
{{translate 'No Data'}}
{{else}}
<div class="list">
	<table class="table">
		<thead>
		<tr>
			{{#each headLayout}}
			<th data-name="{{this.name}}"{{#if this.width}} width="{{this.width}}"{{/if}}>
				{{#if this.customLabel}}
				{{../customLabel}}
				{{else}}
				{{translate this.name category='fields' scope=../../scope}}
				{{/if}}
			</th>
			{{/each}}
		</tr>
		</thead>
		<tbody>
		{{#each rowsDefs}}
		<tr class="list-row" data-type="{{this.type}}" data-id="{{this.id}}"></tr>
		{{/each}}
		</tbody>
	</table>
</div>
{{/if}}
