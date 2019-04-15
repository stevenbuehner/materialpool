const state = {
    title: '',
    description: '',
    rating: null,
    fromBot: false,
    author: null,
    keywordIds: [],
    bibleverses: [],
};

const getters = {

    getTitle: (state) => {
        return state.title;
    },

    getDescription: (state) => {
        return state.description;
    },

    getRating: (state) => {
        return state.rating;
    },

    getFromBot: (state) => {
        return state.fromBot;
    },

    getAuthor: (state) => {
        return state.author;
    },

    getKeywordIds: (state) => {
        return state.keywordIds;
    },

    getBibleverseIds: (state) => {
        return state.bibleverses;
    },

};

const mutations = {


    setTitle: (state, title) => {
        state.title = title;
    },

    setDescription: (state, description) => {
        state.description = description;
    },

    setRating: (state, rating) => {
        state.rating = rating;
    },

    setFromBot: (state, fromBot) => {
        state.fromBot = fromBot;
    },

    setAuthor: (state, author) => {
        state.author = author;
    },

    setKeywordIds: (state, keywordIds) => {
        state.keywordIds = keywordIds.slice(0); // Assign Copy of keywordIds Array
    },

    setBibleverseIds: (state, bibleverses) => {
        state.bibleverses = bibleverses.slice(0); // // Assign Copy of bibleverses Array
    },

};

const actions = {};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};