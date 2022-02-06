

document.addEventListener('DOMContentLoaded', function (){
    const form = document.getElementById('form');
    form.addEventListener('submit', formSend);

    async function formSend(e){
        e.preventDefault();
        let error = formValidate(form);
        if(error === 0){

        }else{
            alert('Заполните поля');
        }

    }

    function formValidate(form){
        let error = 0;
        let formReq = document.querySelectorAll('._req');

        for (let index = 0; index < formReq.length; index++){
            const input = formReq[index];
            formRemoveError(input);

            if (input.classList.contains('_email')){
                if (emailTest(input)) {
                    formAddError(input);
                    error++;
                }
            }else if(input.getAttribute("type") === "checkbox" && input.checkbox === false){
                    formAddError(input);
                    error++;
                }else{
                if(input.value === '') {
                    formAddError(input);
                    error++;
                }
            }
            }
    return error;
        }


    function formAddError(input){
        input.parentElement.classList.add('_error');
        input.classList.add('_error');
    }

    function formRemoveError(input){
        input.parentElement.classList.remove('_error');
        input.classList.remove('_error');
    }

//test
    function emailTest(input){
        return !/^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,8})+$/.test(input.value);
    }


//File
    const formFile = document.getElementById('formFile');
    // preview
    const formPreview = document.getElementById('formImg');

    // изменения в инпуте file

    formImg.addEventListener('change',()=>{
        uploadFile(formImg.files[0]);
    })


});





let sendForm = document.querySelector('#send');
sendForm.onclick = function (event) {
    event.preventDefault();

}

