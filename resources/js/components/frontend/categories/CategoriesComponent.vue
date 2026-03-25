<template>
    <LoadingComponent :props="loading" />

    <section class="mb-10 sm:mb-20">
        <div class="container">
            <div class="mb-8">
                <h1 class="text-[26px] sm:text-4xl font-bold leading-tight capitalize">
                    {{ $t("label.browse_by_categories") }}
                </h1>
                <p class="mt-2 text-text text-sm sm:text-base capitalize">
                    {{ flattenedCategories.length }} {{ $t("label.categories") }}
                </p>
            </div>

            <div v-if="flattenedCategories.length > 0"
                class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                <router-link v-for="category in flattenedCategories" :key="category.slug"
                    :to="{ name: 'frontend.product', query: { category: category.slug } }"
                    class="group rounded-2xl bg-white border border-gray-100 shadow-xs overflow-hidden transition-all duration-300 hover:shadow-md hover:border-primary/20">
                    <div class="relative">
                        <img v-if="category.cover || category.thumb" :src="category.cover || category.thumb"
                            :alt="category.name"
                            class="w-full h-[132px] sm:h-[148px] object-cover transition-transform duration-300 group-hover:scale-105" />
                        <div v-else
                            class="w-full h-[132px] sm:h-[148px] bg-gray-50 flex items-center justify-center text-sm text-slate-400">
                            {{ category.name }}
                        </div>
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-sm sm:text-base font-semibold capitalize transition-colors duration-300 group-hover:text-primary">
                            {{ category.name }}
                        </h3>
                    </div>
                </router-link>
            </div>
        </div>
    </section>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";

export default {
    name: "CategoriesComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false
            }
        };
    },
    computed: {
        categoryTree() {
            return this.$store.getters["frontendProductCategory/trees"];
        },
        flattenedCategories() {
            // Flatten the full tree into a single unique list so the page can "show all categories".
            const out = [];
            const seen = new Set();

            const walk = (nodes) => {
                if (!Array.isArray(nodes)) return;
                for (const node of nodes) {
                    if (!node || !node.slug) continue;
                    if (!seen.has(node.slug)) {
                        seen.add(node.slug);
                        out.push(node);
                    }
                    if (Array.isArray(node.children) && node.children.length > 0) {
                        walk(node.children);
                    }
                }
            };

            walk(this.categoryTree);
            return out;
        }
    },
    mounted() {
        if (!Array.isArray(this.categoryTree) || this.categoryTree.length === 0) {
            this.loading.isActive = true;
            this.$store.dispatch("frontendProductCategory/trees")
                .then(() => {
                    this.loading.isActive = false;
                })
                .catch(() => {
                    this.loading.isActive = false;
                });
        }
    },
};
</script>

