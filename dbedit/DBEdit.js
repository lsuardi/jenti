
/*
 *
 */
function esegui_selettore_azioni( selettore )
{
    
    alert( "azione selezionata:"
         + " " + selettore.selectedIndex
         + " " + selettore.options[selettore.selectedIndex].text
         + " " + selettore.options[selettore.selectedIndex].value
    );
    

    // trova l'oggetto form
    var tef = document.getElementById("DBEF_CLASSE_PROFESSORE");
    
    tef.action = "RegistroVirtuale.classe_studente.php";

    // salva l'azione nel campo nascosto azione (stesso nome del selettore azione)
//    tef.azione.value = selettore.selectedIndex;

    // salva valore della chiave (primary key) nel campo nascosto pk_values
//    tef.pk_values.value = selettore.options[selettore.selectedIndex].value;

    // submit form
    tef.submit();
}
