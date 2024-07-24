import { reactive } from "vue";

// TODO: Make dynamic sizing config based on container size
export const sharedState = reactive({
    plotWidth: 280,
    plotHeight: 280,
});
