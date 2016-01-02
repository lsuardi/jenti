
$(document).ready(function() 
{
    $("#select-action").change(function(event) 
    {
        var optionSelected = $("option:selected", this);
        var valueSelected = this.value;
        var selectedIndex = this.selectedIndex;
        
        alert(optionSelected + " " + valueSelected + " " + selectedIndex);
    });
});   
