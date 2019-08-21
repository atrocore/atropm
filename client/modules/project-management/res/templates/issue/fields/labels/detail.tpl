{{#each map}}
<span class="label-pill" data-id="{{this.id}}">{{this.name}}</span>
{{/each}}
<style>
.label-pill {
    display: inline-block;
    padding: 2px 5px;
    border-radius: 7px;
    background-color: #efefef;
    color: #333333;
    margin-right: 2px;
}
</style>
