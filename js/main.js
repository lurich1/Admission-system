// Toggle mobile menu
document.addEventListener("DOMContentLoaded", () => {
  // Form validation
  const forms = document.querySelectorAll("form")

  forms.forEach((form) => {
    form.addEventListener("submit", (event) => {
      const requiredFields = form.querySelectorAll("[required]")
      let isValid = true

      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          isValid = false
          field.classList.add("error")

          // Create error message if it doesn't exist
          let errorMessage = field.parentElement.querySelector(".field-error")
          if (!errorMessage) {
            errorMessage = document.createElement("div")
            errorMessage.className = "field-error"
            errorMessage.textContent = "This field is required"
            field.parentElement.appendChild(errorMessage)
          }
        } else {
          field.classList.remove("error")
          const errorMessage = field.parentElement.querySelector(".field-error")
          if (errorMessage) {
            errorMessage.remove()
          }
        }
      })

      if (!isValid) {
        event.preventDefault()
      }
    })
  })

  // Remove error styling on input
  const inputs = document.querySelectorAll("input, select, textarea")

  inputs.forEach((input) => {
    input.addEventListener("input", function () {
      if (this.value.trim()) {
        this.classList.remove("error")
        const errorMessage = this.parentElement.querySelector(".field-error")
        if (errorMessage) {
          errorMessage.remove()
        }
      }
    })
  })

  // Password confirmation validation
  const passwordField = document.getElementById("password")
  const confirmPasswordField = document.getElementById("confirm_password")

  if (passwordField && confirmPasswordField) {
    confirmPasswordField.addEventListener("input", function () {
      if (this.value !== passwordField.value) {
        this.classList.add("error")

        // Create error message if it doesn't exist
        let errorMessage = this.parentElement.querySelector(".field-error")
        if (!errorMessage) {
          errorMessage = document.createElement("div")
          errorMessage.className = "field-error"
          errorMessage.textContent = "Passwords do not match"
          this.parentElement.appendChild(errorMessage)
        } else {
          errorMessage.textContent = "Passwords do not match"
        }
      } else {
        this.classList.remove("error")
        const errorMessage = this.parentElement.querySelector(".field-error")
        if (errorMessage) {
          errorMessage.remove()
        }
      }
    })
  }

  // Auto-hide success and error messages after 5 seconds
  const messages = document.querySelectorAll(".success-message, .error-message")

  messages.forEach((message) => {
    setTimeout(() => {
      message.style.opacity = "0"
      setTimeout(() => {
        message.style.display = "none"
      }, 500)
    }, 5000)
  })

  // Program selection validation (prevent duplicate selections)
  const programFirstChoice = document.getElementById("program_first_choice")
  const programSecondChoice = document.getElementById("program_second_choice")
  const programThirdChoice = document.getElementById("program_third_choice")

  if (programFirstChoice && programSecondChoice && programThirdChoice) {
    const validateProgramChoices = () => {
      const firstChoice = programFirstChoice.value
      const secondChoice = programSecondChoice.value
      const thirdChoice = programThirdChoice.value

      // Reset all options
      Array.from(programFirstChoice.options).forEach((option) => {
        option.disabled = false
      })

      Array.from(programSecondChoice.options).forEach((option) => {
        option.disabled = false
      })

      Array.from(programThirdChoice.options).forEach((option) => {
        option.disabled = false
      })

      // Disable selected options in other dropdowns
      if (firstChoice) {
        Array.from(programSecondChoice.options).forEach((option) => {
          if (option.value === firstChoice && option.value !== "") {
            option.disabled = true
          }
        })

        Array.from(programThirdChoice.options).forEach((option) => {
          if (option.value === firstChoice && option.value !== "") {
            option.disabled = true
          }
        })
      }

      if (secondChoice) {
        Array.from(programFirstChoice.options).forEach((option) => {
          if (option.value === secondChoice && option.value !== "") {
            option.disabled = true
          }
        })

        Array.from(programThirdChoice.options).forEach((option) => {
          if (option.value === secondChoice && option.value !== "") {
            option.disabled = true
          }
        })
      }

      if (thirdChoice) {
        Array.from(programFirstChoice.options).forEach((option) => {
          if (option.value === thirdChoice && option.value !== "") {
            option.disabled = true
          }
        })

        Array.from(programSecondChoice.options).forEach((option) => {
          if (option.value === thirdChoice && option.value !== "") {
            option.disabled = true
          }
        })
      }
    }

    programFirstChoice.addEventListener("change", validateProgramChoices)
    programSecondChoice.addEventListener("change", validateProgramChoices)
    programThirdChoice.addEventListener("change", validateProgramChoices)

    // Initial validation
    validateProgramChoices()
  }
})

