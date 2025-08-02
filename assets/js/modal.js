window.onload = () => {

    // On récupère tous les boutons d'ouverture de modale
    const modalButtons = document.querySelectorAll("[data-toggle=modal]");
    for(let button of modalButtons){
        button.addEventListener("click", function(e){
            // On empêche la navigation
            e.preventDefault();
            // On récupère le data-target
            let target = this.dataset.target
            let id = this.dataset.id
            // On récupère la bonne modale
            let modal = document.querySelector(target);
            // On affiche la modale
            modal.classList.add("show");

            // On récupère les boutons de suppression
            const modalDelete = modal.querySelectorAll("[data-dismiss=dialog-delete]");
            console.log(modalDelete);
            for(let supprime of modalDelete){
                supprime.addEventListener("click",function(){

                    document.querySelector(".modal-footer a").href = `/admin/announce/delete/${id}`;
                    modal.classList.remove("show");



                });
            }

            // On récupère les boutons de fermeture
            const modalClose = modal.querySelectorAll("[data-dismiss=dialog]");
            for(let close of modalClose){
                close.addEventListener("click", () => {
                    modal.classList.remove("show");

                });
            }

            // On gère la fermeture lors du clic sur la zone grise
            modal.addEventListener("click", function(){
                modal.classList.remove("show");

            });
            // On évite la propagation du clic d'un enfant à son parent
            modal.children[0].addEventListener("click", function(e){
                e.stopPropagation();
            })


        });
    }
    let active = document.querySelectorAll("[type=checkbox]");
    for(let button of active){
        button.addEventListener("click",function(){
            let xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = () => {

            }
            xmlhttp.open("get",`/admin/announce/active/${this.dataset.id}`)
            xmlhttp.send()
        });
    }

}
