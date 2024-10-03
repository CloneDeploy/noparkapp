<script lang="text/javascript">

async function createQR(url) {
    const response = await fetch(url);
    const { errorcode, errormessage, shorturl } = await response.json();

    if(errorcode) {
        Livewire.dispatch('dataError', { data: errormessage });
    } else {
        Livewire.dispatch('dataFetched', { data: shorturl });
    }
}

document.addEventListener('livewire:initialized', async () => {
    document.addEventListener('create-qr', async (event) => {
        console.log('creating qr from javascript');
        const short = await createQR(event.detail[0]);
        //Livewire.dispatch('dataFetched', { data: short });
    });

    document.addEventListener('refresh-page', async (event) => {
        // refresh page
        console.log(event.detail);
        window.location.reload();
    });

    document.addEventListener('create-ok', async (event) => {
        console.log('creating qr from php');
        const short = await createQR(event.detail[0]);
        console.log(short);
    });
});


</script>
