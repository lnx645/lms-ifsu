import {router} from "@inertiajs/svelte"
import {toast} from "svelte-sonner"
export const initializeFlashToast = ()=>{
    router.on("flash",function(event){
        let flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as {
            type:string,
            message:string
        }|undefined
        if (!data) {
            return
        }
        toast[data.type](data.message)
    })
}