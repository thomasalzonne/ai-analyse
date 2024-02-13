import { instantMeiliSearch } from "@meilisearch/instant-meilisearch";
import instantsearch from "instantsearch.js";
import {
    searchBox,
    hits,
    refinementList,
    rangeSlider,
    sortBy,
    stats,
} from "instantsearch.js/es/widgets";
import { connectPagination } from "instantsearch.js/es/connectors";

let url = import.meta.env.VITE_FRONT_MEILISEARCH_HOST;
let apiKey = import.meta.env.VITE_FRONT_MEILISEARCH_API_KEY;
