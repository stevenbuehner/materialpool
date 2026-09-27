<template>
  <router-link v-bind="$attrs" v-if="to" :to="to" class="navbar-brand"><slot/></router-link>
  <a v-bind="$attrs" v-else :href="href || '#'" class="navbar-brand"><slot/></a>
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
