<template>
    <div class="flex flex-col h-screen">
        <nav class="h-16 text-white flex items-center px-4 bg-red-green-stripes border-b-2 border-red-400">
            <div class="flex items-center">
                <span class="text-xl font-bold"></span>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
            <!-- Column 1 -->
            <div class="w-full md:w-2/5 p-4 text-right">
                <h2 class="mb-4 text-3xl font-bold">{{ a.name }}</h2>

                <div class="relative ml-auto rounded-xl shadow-xl border w-[30rem] h-[30rem] overflow-hidden">
                    <img :src="a.image.asset.url" alt="Image Description" class="w-full h-full object-cover">

                    <!-- Overlay with Checkmark -->
                    <div v-if='leftWin' class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                        <!-- Checkmark Icon -->
                        <svg class="w-24 h-24 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <!-- Checkmark Path -->
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <div class='mt-4 text-sm text-gray-700'>
                    Press the left arrow key to choose {{ a.name }}
                </div>
            </div>

            <!-- Column 2 -->
            <div class="w-full md:w-2/5 border-l p-4">
                <h2 class="mb-4 text-3xl font-bold">{{ b.name }}</h2>

                <div class="relative rounded-xl shadow-xl border w-[30rem] h-[30rem] overflow-hidden">
                    <img :src="b.image.asset.url" alt="Image Description" class="w-full h-full object-cover">

                    <!-- Overlay with Checkmark -->
                    <div v-if='rightWin' class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                        <!-- Checkmark Icon -->
                        <svg class="w-24 h-24 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <!-- Checkmark Path -->
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <div class='mt-4 text-sm text-gray-700'>
                    Press the right arrow key to choose {{ b.name }}
                </div>
            </div>

            <!-- Column 3 -->
            <div class="w-full md:flex-1 border-l p-4 overflow-y-auto">
                <h2 class="mb-4 text-3xl font-bold">Ranked List</h2>
                <TransitionGroup tag="ul" name="fade" class="relative list-none">
                    <li v-for="item in rankedToys" class="mb-3 border rounded p-1 flex shadow" :key="item">
                        <div class="w-[4rem] h-[4rem] overflow-hidden">
                            <img :src="item.image.asset.url" alt="Image Description" class="w-full h-full object-cover">
                        </div>
                        <div class='ml-2'>
                        <div class='text-xl font-bold'>{{ item.name }}</div>
                            <div><span class='text-sm'>{{ item.rating }}</span></div>
                        </div>
                    </li>
                </TransitionGroup>
            </div>
        </div>
    </div>
</template>

<script>
import {Head} from "@inertiajs/vue3";

export default {
  components: {Head},
  props: {
    initialToys: Array
  },

  data() {
    return {
      a: {},
      b: {},
      leftWin: false,
      rightWin: false,
      toys: JSON.parse(JSON.stringify(this.initialToys))
    }
  },

  created() {
    this.populateAB();
  },

  mounted() {
    window.addEventListener('keydown', this.handleKeydown);
    window.addEventListener('keyup', this.handleKeyup);

    Echo.channel('main').listen('.ratings.updated', event => this.update(event.ratings));
  },

  beforeUnmount() {
    window.removeEventListener('keydown', this.handleKeydown);
    window.addEventListener('keyup', this.handleKeyup);

    Echo.leave('main');
  },

  computed: {
    rankedToys() {
      return this.toys.slice().sort((a, b) => b.rating - a.rating);
    }
  },

  methods: {
    populateAB() {
      const toysLength = this.toys.length;
      const indices = new Set();
      const current = [this.a._id, this.b._id];

      while (indices.size < 2) {
        const randomIndex = Math.floor(Math.random() * toysLength);

        if (current.includes(this.toys[randomIndex]._id)) {
          continue;
        }

        indices.add(randomIndex);
      }

      const [index1, index2] = Array.from(indices);

      this.a = this.toys[index1];
      this.b = this.toys[index2];
    },

    findToy(id) {
      return this.toys.find((toy) => toy._id === id);
    },

    handleKeyup(event) {
      switch (event.key) {
        case 'ArrowLeft':
          this.vote(1);
          break;
        case 'ArrowRight':
          this.vote(0);
          break;
        case 'ArrowDown':
          this.vote(0.5);
          break;
      }

      setTimeout(() => {
        this.leftWin = false;
        this.rightWin = false;
      }, 50)
    },

    handleKeydown(event) {
      switch (event.key) {
        case 'ArrowLeft':
          this.leftWin = true;
          break;
        case 'ArrowRight':
          this.rightWin = true;
          break;
        case 'ArrowDown':
          this.leftWin = true;
          this.rightWin = true;
          break;
      }
    },

    update(data) {
      Object.keys(data).forEach(key => {
        let toy = this.findToy(key);

        if (toy) {
          toy.rating = data[key];
        }
      })
    },

    vote(result) {
      axios
        .post('/vote', {
          'a_toy_id': this.a._id,
          'b_toy_id': this.b._id,
          'a_toy_rating': this.a.rating,
          'b_toy_rating': this.b.rating,
          'a_result': result,
        })
        .then(response => this.update(response.data));

      this.populateAB();
    }
  }
}
</script>

<style>

/* 1. declare transition */
.fade-move,
.fade-enter-active,
.fade-leave-active {
    transition: all 0.5s cubic-bezier(0.55, 0, 0.1, 1);
}

/* 2. declare enter from and leave to state */
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: scaleY(0.01) translate(30px, 0);
}

/* 3. ensure leaving items are taken out of layout flow so that moving
      animations can be calculated correctly. */
.fade-leave-active {
    position: absolute;
}
</style>