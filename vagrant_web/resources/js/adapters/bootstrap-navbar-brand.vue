<template>
  <router-link v-if="to" v-bind="$attrs" :to="to" class="navbar-brand"><slot/></router-link>
  <a v-else v-bind="$attrs" :href="href || '#'" class="navbar-brand"><slot/></a>
</template>

<script>
export default {
    name: 'BNavbarBrand',
    inheritAttrs: false,
    props: {
        href: {type: String, default: null},
        to: {type: [String, Object], default: null},
    },
};
</script>
